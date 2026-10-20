<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subject;
use App\Models\Chapter;
use App\Models\TutorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    /**
     * Get live summary KPI metrics for the executive dashboard.
     * Returns: active_learners, pending_reviews_count, pending_tutors_count,
     *          total_subjects, total_chapters.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        // Active students (non-admins)
        $activeLearners = User::where('is_admin', 0)->count();

        // Pending platform reviews count
        $pendingReviewsCount = DB::table('platform_reviews')->where('status', 'pending')->count();

        // Pending tutors count
        $pendingTutorsCount = TutorProfile::where('status', 'pending')->count();

        // Curriculum coverage
        $totalSubjects = Subject::count();
        $totalChapters = Chapter::count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'active_learners'       => $activeLearners,
                'pending_reviews_count' => $pendingReviewsCount,
                'pending_tutors_count'  => $pendingTutorsCount,
                'total_subjects'        => $totalSubjects,
                'total_chapters'        => $totalChapters,
            ],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    /**
     * Grade 9–12 student enrollment & stream distribution.
     * Queries student_profiles table linked via foreign key grade_id to grades table.
     */
    public function studentDistribution(?Request $request = null): JsonResponse
    {
        if ($request && $request->user() && !$request->user()->is_admin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        // Ensure grades 9, 10, 11, and 12 are identified from the `grades` table
        $gradeLevels = [9, 10, 11, 12];
        $data = [];

        $hasLevel    = Schema::hasTable('grades') && Schema::hasColumn('grades', 'level');
        $hasStream   = Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'stream');
        $hasStreamId = Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'stream_id');

        foreach ($gradeLevels as $level) {
            // Find the grade ID for this level dynamically (by exact name or pattern)
            $grade = DB::table('grades')
                ->where(function ($q) use ($level, $hasLevel) {
                    if ($hasLevel) {
                        $q->where('level', $level);
                    }
                    $q->orWhere('name', "Grade {$level}")
                      ->orWhere('name', "grade {$level}")
                      ->orWhere('name', (string)$level)
                      ->orWhere('name', 'like', "%Grade {$level}%")
                      ->orWhere('name', 'like', "%Grade {$level}")
                      ->orWhere('name', 'like', "%{$level}%");
                })
                ->first();

            if (!$grade) {
                $data[] = [
                    'grade'   => "Grade {$level}",
                    'general' => $level <= 10 ? 0 : null,
                    'natural' => $level >= 11 ? 0 : null,
                    'social'  => $level >= 11 ? 0 : null,
                    'total'   => 0,
                ];
                continue;
            }

            // Base query for student profiles linked to this grade_id
            $baseQuery = DB::table('student_profiles')
                ->where('student_profiles.grade_id', $grade->id);

            if ($level === 9 || $level === 10) {
                // Grades 9 & 10: Single Green Bar (General Core)
                $total = (clone $baseQuery)->count();

                $data[] = [
                    'grade'   => "Grade {$level}",
                    'general' => $total,
                    'natural' => null,
                    'social'  => null,
                    'total'   => $total,
                ];
            } else {
                // Grades 11 & 12: Split by Stream (Natural Science Purple vs Social Science Blue)
                // Filter where stream contains 'natural' or stream_id == 1
                $naturalCount = (clone $baseQuery)
                    ->where(function ($q) use ($hasStream, $hasStreamId) {
                        $hasCondition = false;
                        if ($hasStream) {
                            $q->where(function ($sq) {
                                $sq->whereRaw("LOWER(TRIM(COALESCE(student_profiles.stream, ''))) LIKE '%natural%'");
                            });
                            $hasCondition = true;
                        }
                        if ($hasStreamId) {
                            if ($hasCondition) {
                                $q->orWhere('student_profiles.stream_id', 1);
                            } else {
                                $q->where('student_profiles.stream_id', 1);
                            }
                            $hasCondition = true;
                        }
                        if (!$hasCondition) {
                            $q->whereRaw('0 = 1');
                        }
                    })
                    ->count();

                // Filter where stream contains 'social' or stream_id == 2
                $socialCount = (clone $baseQuery)
                    ->where(function ($q) use ($hasStream, $hasStreamId) {
                        $hasCondition = false;
                        if ($hasStream) {
                            $q->where(function ($sq) {
                                $sq->whereRaw("LOWER(TRIM(COALESCE(student_profiles.stream, ''))) LIKE '%social%'");
                            });
                            $hasCondition = true;
                        }
                        if ($hasStreamId) {
                            if ($hasCondition) {
                                $q->orWhere('student_profiles.stream_id', 2);
                            } else {
                                $q->where('student_profiles.stream_id', 2);
                            }
                            $hasCondition = true;
                        }
                        if (!$hasCondition) {
                            $q->whereRaw('0 = 1');
                        }
                    })
                    ->count();

                // Total students enrolled in this grade
                $totalInGrade = (clone $baseQuery)->count();

                // Handle unassigned / NULL stream students (e.g. fresh cPanel registrations)
                $unassigned = max(0, $totalInGrade - ($naturalCount + $socialCount));
                if ($unassigned > 0) {
                    if (($naturalCount + $socialCount) === 0) {
                        // All unassigned: split 50/50 so students are visible on the chart
                        $naturalCount = (int) ceil($totalInGrade / 2);
                        $socialCount  = $totalInGrade - $naturalCount;
                    } else {
                        // Distribute unassigned students proportionally
                        $ratio = $naturalCount / ($naturalCount + $socialCount);
                        $addNatural = (int) round($unassigned * $ratio);
                        $naturalCount += $addNatural;
                        $socialCount  += ($unassigned - $addNatural);
                    }
                }

                $data[] = [
                    'grade'   => "Grade {$level}",
                    'general' => null,
                    'natural' => $naturalCount,
                    'social'  => $socialCount,
                    'total'   => $totalInGrade,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }
}
