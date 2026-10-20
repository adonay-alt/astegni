<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\NationalExam;
use App\Models\NationalExamQuestion;
use App\Models\NationalExamSubject;
use App\Models\NationalExamYear;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminNationalExamController extends Controller
{
    // ==========================================
    // 1. NATIONAL EXAMS STUDIO (READ)
    // ==========================================

    /**
     * Subject-first overview endpoint (multi-program aware):
     * GET /api/admin/national-exams/overview?grade={8|12}[&exam_id={id|all}]
     *
     * When exam_id absent or 'all': aggregates ALL exams matching the grade so all
     * 2,014+ Grade-12 questions are visible across Matric / EUEE / ESSLCE programs.
     * When exam_id is an integer: restricts to that specific exam only.
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $this->ensureOfficialExamsExist();
            $grade = $this->resolveStudioGrade($request);
            $examIdParam = $request->input('exam_id', 'all');
            $targetExams = $this->resolveStudioExams($grade, $examIdParam);

            // Always provide both official exams in the exams payload so the admin can toggle
            $allOfficialExams = DB::table('national_exams')->whereIn('id', [1, 2])->orderBy('id', 'asc')->get();
            $examPayload = $allOfficialExams->map(fn ($e) => [
                'id'          => $e->id,
                'grade_id'    => $e->grade_id,
                'grade_level' => (int) $e->id === 2 ? 8 : 12,
                'title'       => $e->title ?? ($e->name ?? ''),
                'short'       => (int) $e->id === 2 ? 'Ministry (Grade 8)' : 'EUEE (Grade 12)',
            ])->values();

            if ($targetExams->isEmpty()) {
                return response()->json(['status' => 'success', 'data' => [], 'exams' => $examPayload, 'stats' => $this->emptyStudioStats()]);
            }

            $allExamIds = $targetExams->pluck('id');
            $yearQuery = DB::table('national_exam_years')->whereIn('national_exam_id', $allExamIds);
            $allExamYearIds = $yearQuery->pluck('id');

            if ($allExamYearIds->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data'   => [],
                    'exams'  => $examPayload,
                    'stats'  => $this->emptyStudioStats(),
                ]);
            }

            $hasYearEc = Schema::hasColumn('national_exam_years', 'year_ec');
            $hasYearGc = Schema::hasColumn('national_exam_years', 'year_gc');
            $hasPublished = Schema::hasColumn('national_exam_years', 'is_published');

            $selectCols = [
                'national_exam_subjects.id as nes_id',
                'national_exam_subjects.name as subject_name',
                'national_exam_subjects.stream_id as stream_id',
                'national_exam_subjects.national_exam_id as exam_id',
                'national_exam_years.id as year_id',
                'national_exam_years.year',
                'national_exams.title as exam_title',
            ];
            if ($hasYearEc) {
                $selectCols[] = 'national_exam_years.year_ec';
            }
            if ($hasYearGc) {
                $selectCols[] = 'national_exam_years.year_gc';
            }
            if ($hasPublished) {
                $selectCols[] = 'national_exam_years.is_published';
            }

            $instances = DB::table('national_exam_subjects')
                ->join('national_exam_years', 'national_exam_subjects.national_exam_year_id', '=', 'national_exam_years.id')
                ->join('national_exams', 'national_exam_subjects.national_exam_id', '=', 'national_exams.id')
                ->whereIn('national_exam_subjects.national_exam_year_id', $allExamYearIds)
                ->select($selectCols)
                ->get();

            $nesIds = $instances->pluck('nes_id')->filter()->unique()->values();
            $countMap = collect();
            if ($nesIds->isNotEmpty()) {
                $countMap = DB::table('national_exam_questions')
                    ->whereIn('national_exam_subject_id', $nesIds)
                    ->select('national_exam_subject_id', DB::raw('COUNT(*) as c'))
                    ->groupBy('national_exam_subject_id')
                    ->pluck('c', 'national_exam_subject_id');
            }

            $grouped = $instances->groupBy('subject_name');
            $data = $grouped->map(function ($rows, $name) use ($countMap) {
                $streamId = null;
                foreach ($rows as $row) {
                    if (!empty($row->stream_id)) {
                        $streamId = (int) $row->stream_id;
                        break;
                    }
                }
                $streamSlug = $this->inferStreamSlug($streamId, (string) $name);

                $rawYears = $rows->map(function ($instance) use ($countMap) {
                    [$yearEc, $yearGc] = $this->resolveCalendarYears($instance);
                    $examTitle = (string) ($instance->exam_title ?? '');

                    return [
                        'id'              => (int) $instance->year_id,
                        'year_id'         => (int) $instance->year_id,
                        'nes_id'          => (int) $instance->nes_id,
                        'subject_id'      => (int) $instance->nes_id,
                        'exam_id'         => (int) $instance->exam_id,
                        'exam_title'      => $examTitle,
                        'exam_short'      => $this->shortExamTitle($examTitle),
                        'year_ec'         => $yearEc,
                        'year_gc'         => $yearGc,
                        'is_published'    => isset($instance->is_published) ? (bool) $instance->is_published : true,
                        'questions_count' => (int) ($countMap[$instance->nes_id] ?? 0),
                    ];
                });

                // Collapse empty G.C. shells onto the real E.C. paper *within the same exam only*.
                $dedupedYears = collect();
                foreach ($rawYears->groupBy(fn ($y) => $y['exam_id'] . ':' . $y['year_ec']) as $group) {
                    $dedupedYears->push(collect($group)->sortByDesc('questions_count')->first());
                }
                $availableYears = $dedupedYears
                    ->sortBy(fn ($y) => (-$y['year_ec'] * 10000) - $y['questions_count'])
                    ->values();

                return [
                    'id'              => (int) $rows->min('nes_id'),
                    'name'            => $name,
                    'code'            => strtoupper(substr((string) $name, 0, 4)),
                    'stream'          => $streamSlug,
                    'stream_id'       => $streamSlug === 'natural' ? 1 : ($streamSlug === 'social' ? 2 : null),
                    'stream_name'     => $this->streamLabel($streamSlug),
                    'total_years'     => $availableYears->count(),
                    'total_questions' => $availableYears->sum('questions_count'),
                    'available_years' => $availableYears,
                ];
            })->values();

            $sortedData = $data->sortByDesc(fn ($s) => $s['total_questions'])->values();

            return response()->json([
                'status' => 'success',
                'data'   => $sortedData,
                'exams'  => $examPayload,
                'stats'  => [
                    'total_programs'  => $examPayload->count(),
                    'total_subjects'  => $sortedData->count(),
                    'total_years'     => $allExamYearIds->count(),
                    'total_questions' => $sortedData->sum('total_questions'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNationalExamController@overview error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load national exams overview: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
    /**
     * Studio endpoint: Returns exam years, subjects, and stats for Grade 12 or Grade 8.
     * GET /api/admin/national-exams?grade={8|12}
     *
     * Strictly queries the 4-tier database hierarchy:
     * national_exams -> national_exam_years -> national_exam_subjects -> national_exam_questions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $grade = $this->resolveStudioGrade($request);

            // 1. Find the target exam in national_exams — prefer explicit exam_id if provided
            $hasName = Schema::hasColumn('national_exams', 'name');
            $hasCode = Schema::hasColumn('national_exams', 'code');
            $hasTitle = Schema::hasColumn('national_exams', 'title');
            $hasGradeId = Schema::hasColumn('national_exams', 'grade_id');

            $exam = null;

            // If caller specifies an explicit exam_id, use it directly
            $explicitExamId = $request->input('exam_id');
            if ($explicitExamId && is_numeric($explicitExamId)) {
                $exam = DB::table('national_exams')->where('id', (int) $explicitExamId)->first();
            }

            if (!$exam && ($hasName || $hasCode)) {
                $exam = DB::table('national_exams')
                    ->where(function ($q) use ($grade, $hasName, $hasCode) {
                        if ($hasName) {
                            $q->where('name', 'like', "%{$grade}%");
                        }
                        if ($hasCode) {
                            if ($hasName) {
                                $q->orWhere('code', 'like', "%{$grade}%");
                            } else {
                                $q->where('code', 'like', "%{$grade}%");
                            }
                        }
                    })
                    ->first();
            }

            if (!$exam && $hasTitle) {
                $exam = DB::table('national_exams')
                    ->where(function ($q) use ($grade) {
                        if ($grade === 12) {
                            $q->where('title', 'like', '%Entrance%')
                              ->orWhere('title', 'like', '%EUEE%')
                              ->orWhere('title', 'like', '%12%');
                        } else {
                            $q->where('title', 'like', '%Grade 8%')
                              ->orWhere('title', 'like', '%8%')
                              ->orWhere('title', 'like', '%Primary%');
                        }
                    })
                    ->first();
            }

            if (!$exam && $hasGradeId && Schema::hasTable('grades')) {
                $gradeRow = DB::table('grades')->where('name', 'like', "%{$grade}%")->first();
                if ($gradeRow) {
                    $exam = DB::table('national_exams')->where('grade_id', $gradeRow->id)->first();
                }
            }

            if (!$exam) {
                $exam = DB::table('national_exams')->first();
            }

            if (!$exam) {
                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        'exam'  => null,
                        'years' => [],
                        'stats' => ['total_years' => 0, 'total_subjects' => 0, 'total_questions' => 0],
                    ],
                    'years'  => [],
                ]);
            }

            // 2. Query real exam years from national_exam_years
            $years = DB::table('national_exam_years')
                ->where('national_exam_id', $exam->id)
                ->orderBy('id', 'desc')
                ->get();

            // If no years exist yet for this exam, return empty structure
            if ($years->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        'exam'  => [
                            'id'          => $exam->id,
                            'grade_level' => $grade,
                            'title'       => $exam->title ?? ($exam->name ?? "Grade {$grade} National Exam"),
                        ],
                        'years' => [],
                        'stats' => ['total_years' => 0, 'total_subjects' => 0, 'total_questions' => 0],
                    ],
                    'years'  => [],
                ]);
            }

            // 3. For each year, query real exam subjects from national_exam_subjects
            $formattedYears = $years->map(function ($year) use ($grade) {
                // Query real subjects for this exam year
                $subjects = DB::table('national_exam_subjects')
                    ->where('national_exam_year_id', $year->id)
                    ->get();

                $yearSubjects = $subjects->map(function ($sub) {
                    // Count questions in national_exam_questions for this specific subject
                    $qCount = DB::table('national_exam_questions')
                        ->where('national_exam_subject_id', $sub->id)
                        ->count();

                    // Detect stream if column exists or infer from stream_id or subject name
                    $name = $sub->name ?? $sub->title ?? $sub->subject_name ?? 'Subject';
                    $stream = $this->inferStreamSlug(isset($sub->stream_id) ? (int) $sub->stream_id : null, $name);
                    $streamId = $stream === 'natural' ? 1 : ($stream === 'social' ? 2 : null);
                    $streamName = $this->streamLabel($stream);

                    return [
                        'id'              => $sub->id,
                        'name'            => $name,
                        'code'            => strtoupper(substr($name, 0, 4)),
                        'stream'          => $stream,
                        'stream_id'       => $streamId,
                        'stream_name'     => $streamName,
                        'questions_count' => $qCount,
                    ];
                });

                [$yearEc, $yearGc] = $this->resolveCalendarYears($year);

                return [
                    'id'              => $year->id,
                    'exam_id'         => $year->national_exam_id,
                    'grade_level'     => $grade,
                    'year_ec'         => $yearEc,
                    'year_gc'         => $yearGc,
                    'is_published'    => (bool) ($year->is_published ?? true),
                    'total_questions' => $yearSubjects->sum('questions_count'),
                    'subjects'        => $yearSubjects,
                ];
            });

            $totalQuestions = $formattedYears->sum('total_questions');
            $firstYearSubjectsCount = $formattedYears->first() ? count($formattedYears->first()['subjects']) : 0;

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'exam'  => [
                        'id'          => $exam->id,
                        'grade_level' => $grade,
                        'title'       => $exam->title ?? ($exam->name ?? "Grade {$grade} National Exam"),
                    ],
                    'years' => $formattedYears,
                    'stats' => [
                        'total_years'     => count($formattedYears),
                        'total_subjects'  => $firstYearSubjectsCount,
                        'total_questions' => $totalQuestions,
                    ],
                ],
                'years'  => $formattedYears,
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNationalExamController@index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load national exams: ' . $e->getMessage(),
                'data'    => [
                    'exam'  => null,
                    'years' => [],
                    'stats' => ['total_years' => 0, 'total_subjects' => 0, 'total_questions' => 0],
                ],
                'years'   => [],
            ], 500);
        }
    }

    /**
     * Ensure the two official national exams exist with correct grade_id.
     * Rule:
     *   ID 1: Grade 12 (EUEE), grade_id = 3
     *   ID 2: Grade 8 (Ministry Primary), grade_id = 5
     */
    private function ensureOfficialExamsExist(): void
    {
        try {
            // Exam 1: Grade 12 (EUEE)
            $exam1 = DB::table('national_exams')->where('id', 1)->first();
            if (!$exam1) {
                DB::table('national_exams')->insert([
                    'id'         => 1,
                    'grade_id'   => 3,
                    'title'      => 'Ethiopian University Entrance Exam (EUEE - Grade 12)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ((int) $exam1->grade_id !== 3) {
                DB::table('national_exams')->where('id', 1)->update(['grade_id' => 3]);
            }

            // Exam 2: Grade 8 (Ministry Regional Primary Exam)
            $exam2 = DB::table('national_exams')->where('id', 2)->first();
            if (!$exam2) {
                DB::table('national_exams')->insert([
                    'id'         => 2,
                    'grade_id'   => 5,
                    'title'      => 'Ministry Regional Primary Exam (Grade 8)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ((int) $exam2->grade_id !== 5) {
                DB::table('national_exams')->where('id', 2)->update(['grade_id' => 5]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to ensure official exams exist: ' . $e->getMessage());
        }
    }

    public function indexNationalExams(Request $request): JsonResponse
    {
        $this->ensureOfficialExamsExist();
        $exams = DB::table('national_exams')
            ->whereIn('id', [1, 2])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Official national exams retrieved successfully.',
            'data'    => $exams,
        ]);
    }

    public function storeNationalExam(Request $request): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Creation of new national exams is locked. Only the two official national exams (Grade 12 and Grade 8) are supported.',
        ], 403);
    }

    public function updateNationalExam(Request $request, $id): JsonResponse
    {
        $this->ensureOfficialExamsExist();
        $exam = NationalExam::findOrFail($id);
        $oldValues = [
            'title'    => $exam->title,
            'grade_id' => $exam->grade_id,
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $exam->update([
            'title' => trim($validated['title']),
        ]);

        AuditLogService::log('national_exam.updated', $exam, $oldValues, [
            'title'    => $exam->title,
            'grade_id' => $exam->grade_id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam updated successfully.',
            'data'    => $exam,
        ]);
    }

    public function deleteNationalExam($id): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Deletion of official national exams is locked. Only the two official national exams (Grade 12 and Grade 8) remain in the database.',
        ], 403);
    }

    // ==========================================
    // 2. EXAM YEARS CRUD
    // ==========================================

    /**
     * Create a new Exam Year and seed standard subjects in national_exam_subjects.
     * POST /api/admin/national-exams/years
     */
    public function storeYear(Request $request): JsonResponse
    {
        try {
            // Sanitize grade and year inputs if formatted strings were passed (e.g. "Grade 12", "2016 E.C.")
            $rawGrade = preg_replace('/[^0-9]/', '', (string) $request->input('grade', $request->input('grade_level', 12)));
            $rawYearEc = preg_replace('/[^0-9]/', '', (string) $request->input('year_ec', $request->input('year', '2017')));
            $rawYearGc = preg_replace('/[^0-9]/', '', (string) $request->input('year_gc', ''));

            $gradeLevel = !empty($rawGrade) ? (int) $rawGrade : 12;
            $yearEc = !empty($rawYearEc) ? (int) $rawYearEc : 2017;
            $yearGc = !empty($rawYearGc) ? (int) $rawYearGc : ($yearEc + 8);
            $isPublished = $request->boolean('is_published', true);

            if ($yearEc < 1990 || $yearEc > 2100) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The year_ec must be between 1990 and 2100.'
                ], 422);
            }

            if (!in_array($gradeLevel, [8, 12])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The grade must be either 8 or 12.'
                ], 422);
            }

            $this->ensureOfficialExamsExist();
            $explicitExamId = $request->input('national_exam_id');
            if ($explicitExamId && is_numeric($explicitExamId)) {
                $examId = (int) $explicitExamId === 2 ? 2 : 1;
            } else {
                $examId = $gradeLevel === 8 ? 2 : 1;
            }

            $exam = DB::table('national_exams')->where('id', $examId)->first();
            $gradeId = $examId === 2 ? 5 : 3;
            $gradeLevel = $examId === 2 ? 8 : 12;

            // Check if year already exists for this exam
            $existingYear = DB::table('national_exam_years')
                ->where('national_exam_id', $examId)
                ->where(function ($q) use ($yearEc) {
                    if (Schema::hasColumn('national_exam_years', 'year')) {
                        $q->where('year', $yearEc);
                    }
                    if (Schema::hasColumn('national_exam_years', 'year_ec')) {
                        $q->orWhere('year_ec', $yearEc);
                    }
                })
                ->first();

            if ($existingYear) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "The year {$yearEc} E.C. already exists for " . ($examId === 2 ? "Grade 8" : "Grade 12") . "."
                ], 422);
            }

            $insertData = [
                'national_exam_id' => $examId,
                'grade_id'         => $gradeId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
            if (Schema::hasColumn('national_exam_years', 'stream_id')) {
                $insertData['stream_id'] = null;
            }
            if (Schema::hasColumn('national_exam_years', 'year')) {
                $insertData['year'] = $yearEc;
            }
            if (Schema::hasColumn('national_exam_years', 'year_ec')) {
                $insertData['year_ec'] = $yearEc;
            }
            if (Schema::hasColumn('national_exam_years', 'year_gc')) {
                $insertData['year_gc'] = $yearGc;
            }
            if (Schema::hasColumn('national_exam_years', 'is_published')) {
                $insertData['is_published'] = $isPublished ? 1 : 0;
            }

            $yearId = DB::table('national_exam_years')->insertGetId($insertData);

            // Seed default subjects for this new year if none exist in national_exam_subjects
            $defaultSubjectsConfig = $gradeLevel === 12 ? [
                ['name' => 'Mathematics', 'code' => 'MATH', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'Physics', 'code' => 'PHYS', 'stream' => 'natural', 'stream_id' => 1],
                ['name' => 'Chemistry', 'code' => 'CHEM', 'stream' => 'natural', 'stream_id' => 1],
                ['name' => 'Biology', 'code' => 'BIOL', 'stream' => 'natural', 'stream_id' => 1],
                ['name' => 'English', 'code' => 'ENG', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'Civics', 'code' => 'CIV', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'Economics', 'code' => 'ECON', 'stream' => 'social', 'stream_id' => 2],
                ['name' => 'Geography', 'code' => 'GEOG', 'stream' => 'social', 'stream_id' => 2],
                ['name' => 'History', 'code' => 'HIST', 'stream' => 'social', 'stream_id' => 2],
            ] : [
                ['name' => 'Mathematics', 'code' => 'MATH', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'General Science', 'code' => 'SCI', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'English', 'code' => 'ENG', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'Social Studies', 'code' => 'SOC', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'Citizenship', 'code' => 'CIT', 'stream' => 'common', 'stream_id' => null],
                ['name' => 'Amharic', 'code' => 'AMH', 'stream' => 'common', 'stream_id' => null],
            ];

            if (DB::table('national_exam_subjects')->where('national_exam_year_id', $yearId)->count() === 0) {
                foreach ($defaultSubjectsConfig as $cfg) {
                    $subInsert = [
                        'national_exam_year_id' => $yearId,
                        'national_exam_id'      => $exam->id,
                        'name'                  => $cfg['name'],
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                    if (Schema::hasColumn('national_exam_subjects', 'grade_id')) {
                        $subInsert['grade_id'] = $grade->id;
                    }
                    if (Schema::hasColumn('national_exam_subjects', 'stream_id')) {
                        $subInsert['stream_id'] = $cfg['stream_id'];
                    }
                    if (Schema::hasColumn('national_exam_subjects', 'description')) {
                        $subInsert['description'] = "{$cfg['name']} Examination Paper";
                    }
                    DB::table('national_exam_subjects')->insert($subInsert);
                }
            }

            // Clear curriculum cache
            if (class_exists(\App\Helpers\CurriculumCacheHelper::class)) {
                \App\Helpers\CurriculumCacheHelper::clearAllCurriculumCache();
            }

            $createdYear = DB::table('national_exam_years')->where('id', $yearId)->first();

            // Record audit trail
            AuditLogService::log('exam_year.created', 'App\Models\NationalExamYear', null, [
                'id' => (int) $yearId,
                'year_ec' => $yearEc,
                'national_exam_id' => $examId,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => "Exam year {$yearEc} E.C. created successfully.",
                'data'    => $createdYear,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create exam year: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeNationalExamYear(Request $request): JsonResponse
    {
        return $this->storeYear($request);
    }

    /**
     * Update an Exam Year (e.g. publication status toggle).
     * PUT/PATCH /api/admin/national-exams/years/{id}
     */
    public function updateYear(Request $request, $id): JsonResponse
    {
        $examYear = DB::table('national_exam_years')->where('id', $id)->first();
        if (!$examYear) {
            return response()->json(['status' => 'error', 'message' => 'Exam year not found'], 404);
        }

        $updates = [];
        if ($request->has('is_published') && Schema::hasColumn('national_exam_years', 'is_published')) {
            $updates['is_published'] = $request->boolean('is_published') ? 1 : 0;
        }
        if ($request->has('year_ec')) {
            if (Schema::hasColumn('national_exam_years', 'year_ec')) {
                $updates['year_ec'] = (int) $request->year_ec;
            }
            if (Schema::hasColumn('national_exam_years', 'year')) {
                $updates['year'] = (int) $request->year_ec;
            }
        } elseif ($request->has('year') && Schema::hasColumn('national_exam_years', 'year')) {
            $updates['year'] = (int) $request->year;
        }
        if ($request->has('year_gc') && Schema::hasColumn('national_exam_years', 'year_gc')) {
            $updates['year_gc'] = (int) $request->year_gc;
        }

        if (!empty($updates)) {
            if (Schema::hasColumn('national_exam_years', 'updated_at')) {
                $updates['updated_at'] = now();
            }
            DB::table('national_exam_years')->where('id', $id)->update($updates);
        }

        $fresh = DB::table('national_exam_years')->where('id', $id)->first();
        [$yearEc, $yearGc] = $this->resolveCalendarYears($fresh);

        // Record audit trail
        AuditLogService::log('exam_year.updated', 'App\Models\NationalExamYear', (array) $examYear, (array) $fresh);

        return response()->json([
            'status'  => 'success',
            'message' => 'Exam year updated successfully.',
            'data'    => [
                'id'           => $fresh->id,
                'exam_id'      => $fresh->national_exam_id ?? 1,
                'year_ec'      => $yearEc,
                'year_gc'      => $yearGc,
                'is_published' => isset($fresh->is_published) ? (bool) $fresh->is_published : true,
            ],
        ]);
    }

    public function updateNationalExamYear(Request $request, $id): JsonResponse
    {
        return $this->updateYear($request, $id);
    }

    /**
     * Delete an Exam Year with cascading questions and subjects deletion.
     * DELETE /api/admin/national-exams/years/{id}
     */
    public function destroyYear($id): JsonResponse
    {
        $examYear = DB::table('national_exam_years')->where('id', $id)->first();
        if (!$examYear) {
            return response()->json(['status' => 'error', 'message' => 'Exam year not found.'], 404);
        }

        DB::transaction(function () use ($examYear) {
            // Delete questions for all subjects belonging to this year
            $subjectIds = DB::table('national_exam_subjects')
                ->where('national_exam_year_id', $examYear->id)
                ->pluck('id')
                ->toArray();

            if (!empty($subjectIds)) {
                DB::table('national_exam_questions')->whereIn('national_exam_subject_id', $subjectIds)->delete();
            }

            if (Schema::hasColumn('national_exam_questions', 'national_exam_year_id')) {
                DB::table('national_exam_questions')->where('national_exam_year_id', $examYear->id)->delete();
            }

            if (Schema::hasColumn('national_exam_questions', 'exam_year_id')) {
                DB::table('national_exam_questions')->where('exam_year_id', $examYear->id)->delete();
            }

            DB::table('national_exam_subjects')->where('national_exam_year_id', $examYear->id)->delete();
            DB::table('national_exam_years')->where('id', $examYear->id)->delete();
        });

        // Record audit trail
        AuditLogService::log('exam_year.deleted', 'App\Models\NationalExamYear', (array) $examYear, null);

        // Clear cache
        if (class_exists(\App\Helpers\CurriculumCacheHelper::class)) {
            \App\Helpers\CurriculumCacheHelper::clearAllCurriculumCache();
        }

        $yearLabel = $examYear->year_ec ?? ($examYear->year ?? $id);

        return response()->json([
            'status'  => 'success',
            'message' => "Exam year {$yearLabel} E.C. and all associated question papers deleted successfully.",
        ]);
    }

    public function deleteNationalExamYear($id): JsonResponse
    {
        return $this->destroyYear($id);
    }

    // ==========================================
    // 3. EXAM QUESTIONS CRUD & BATCH IMPORT
    // ==========================================

    /**
     * Get all questions for a specific Year & Subject.
     * GET /api/admin/national-exams/years/{yearId}/subjects/{subjectId}/questions
     *
     * Strictly queries national_exam_questions using foreign key national_exam_subject_id.
     */
    public function getQuestions(Request $request, $yearId, $subjectId): JsonResponse
    {
        $nesId = $request->query('nes_id');

        if (!$nesId) {
            $nes = DB::table('national_exam_subjects')
                ->where('id', $subjectId)
                ->first();

            if (!$nes) {
                $nes = DB::table('national_exam_subjects')
                    ->where('national_exam_year_id', $yearId)
                    ->where('id', $subjectId)
                    ->first();
            }
            $nesId = $nes ? $nes->id : $subjectId;
        }

        if ($request->boolean('count_only')) {
            $count = DB::table('national_exam_questions')
                ->where('national_exam_subject_id', $nesId)
                ->count();

            return response()->json([
                'status' => 'success',
                'data'   => [],
                'count'  => $count,
            ]);
        }

        $rawQuestions = DB::table('national_exam_questions')
            ->where('national_exam_subject_id', $nesId)
            ->orderBy('question_number', 'asc')
            ->get();

        $questions = $rawQuestions->map(function ($q) {
            $options = null;
            if (isset($q->options)) {
                $options = is_string($q->options) ? json_decode($q->options, true) : $q->options;
            }
            if (!$options || !is_array($options)) {
                $options = [
                    'A' => $q->option_a ?? '',
                    'B' => $q->option_b ?? '',
                    'C' => $q->option_c ?? '',
                    'D' => $q->option_d ?? '',
                ];
            }

            $rawCorrect = strtoupper(trim((string) ($q->correct_answer ?? 'A')));
            $correct = match ($rawCorrect) {
                'OPTION_A', 'A' => 'A',
                'OPTION_B', 'B' => 'B',
                'OPTION_C', 'C' => 'C',
                'OPTION_D', 'D' => 'D',
                default => 'A',
            };

            return [
                'id'              => $q->id,
                'exam_year_id'    => (int) ($q->national_exam_year_id ?? 0),
                'subject_id'      => (int) $q->national_exam_subject_id,
                'question_number' => (int) ($q->question_number ?? 1),
                'question_text'   => $q->question_text ?? ($q->question ?? ''),
                'diagram_url'     => $q->diagram_url ?? ($q->image_url ?? null),
                'options'         => $options,
                'correct_answer'  => $correct,
                'explanation'     => $q->explanation ?? '',
                'created_at'      => $q->created_at ?? null,
                'updated_at'      => $q->updated_at ?? null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $questions,
            'count'  => count($questions),
        ]);
    }

    public function getYearSubjectQuestions(Request $request, $yearId, $subjectId): JsonResponse
    {
        return $this->getQuestions($request, $yearId, $subjectId);
    }

    /**
     * Create single question manually.
     * POST /api/admin/national-exams/questions
     */
    public function storeQuestion(Request $request): JsonResponse
    {
        $subjectId = (int) $request->input('subject_id', $request->input('national_exam_subject_id'));
        $yearId = (int) $request->input('exam_year_id', $request->input('national_exam_year_id'));
        $qText = (string) $request->input('question_text', $request->input('question', ''));
        $diagram = $request->input('diagram_url', $request->input('image_url'));
        $explanation = (string) $request->input('explanation', '');

        $subject = DB::table('national_exam_subjects')->where('id', $subjectId)->first();
        if (!$subject) {
            return response()->json(['status' => 'error', 'message' => 'Subject not found'], 404);
        }

        $targetYearId = $yearId ?: ($subject->national_exam_year_id ?? 1);

        $optionsInput = $request->input('options', []);
        if (is_array($optionsInput)) {
            $optA = $optionsInput['A'] ?? ($optionsInput['a'] ?? ($request->input('option_a', '')));
            $optB = $optionsInput['B'] ?? ($optionsInput['b'] ?? ($request->input('option_b', '')));
            $optC = $optionsInput['C'] ?? ($optionsInput['c'] ?? ($request->input('option_c', '')));
            $optD = $optionsInput['D'] ?? ($optionsInput['d'] ?? ($request->input('option_d', '')));
        } else {
            $optA = $request->input('option_a', '');
            $optB = $request->input('option_b', '');
            $optC = $request->input('option_c', '');
            $optD = $request->input('option_d', '');
        }

        $rawCorrect = strtoupper(trim((string) $request->input('correct_answer', 'A')));
        $correctOption = match ($rawCorrect) {
            'A', 'OPTION_A' => 'option_a',
            'B', 'OPTION_B' => 'option_b',
            'C', 'OPTION_C' => 'option_c',
            'D', 'OPTION_D' => 'option_d',
            default => 'option_a',
        };

        $qNumber = (int) $request->input('question_number', 0);
        if ($qNumber <= 0 && Schema::hasColumn('national_exam_questions', 'question_number')) {
            $maxQ = DB::table('national_exam_questions')
                ->where('national_exam_subject_id', $subject->id)
                ->max('question_number') ?? 0;
            $qNumber = $maxQ + 1;
        }

        $insertData = [
            'national_exam_subject_id' => $subject->id,
            'national_exam_year_id'    => $targetYearId,
            'national_exam_id'         => $subject->national_exam_id ?? 1,
            'grade_id'                 => $subject->grade_id ?? ($subject->national_exam_id == 2 ? 5 : 3),
            'option_a'                 => $optA,
            'option_b'                 => $optB,
            'option_c'                 => $optC,
            'option_d'                 => $optD,
            'correct_answer'           => $correctOption,
            'explanation'              => $explanation,
            'created_at'               => now(),
            'updated_at'               => now(),
        ];

        if (Schema::hasColumn('national_exam_questions', 'question_text')) {
            $insertData['question_text'] = $qText;
        }
        if (Schema::hasColumn('national_exam_questions', 'question')) {
            $insertData['question'] = $qText;
        }
        if (Schema::hasColumn('national_exam_questions', 'question_number')) {
            $insertData['question_number'] = $qNumber;
        }
        if (Schema::hasColumn('national_exam_questions', 'image_url')) {
            $insertData['image_url'] = $diagram;
        } elseif (Schema::hasColumn('national_exam_questions', 'diagram_url')) {
            $insertData['diagram_url'] = $diagram;
        }

        $qId = DB::table('national_exam_questions')->insertGetId($insertData);
        $created = DB::table('national_exam_questions')->where('id', $qId)->first();

        // Record audit trail
        AuditLogService::log('exam_question.created', 'App\Models\NationalExamQuestion', null, [
            'id' => (int) $qId,
            'subject_id' => $subject->id,
            'question_number' => (int) ($created->question_number ?? $qNumber),
            'question' => mb_substr($created->question ?? '', 0, 100),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Question created successfully.',
            'data'    => [
                'id'              => $created->id,
                'exam_year_id'    => $created->national_exam_year_id ?? $targetYearId,
                'subject_id'      => $created->national_exam_subject_id ?? $subject->id,
                'subject_name'    => $subject->name,
                'question_number' => (int) ($created->question_number ?? $qNumber),
                'question_text'   => $created->question,
                'diagram_url'     => $created->image_url ?? ($created->diagram_url ?? null),
                'options'         => [
                    'A' => $created->option_a ?? '',
                    'B' => $created->option_b ?? '',
                    'C' => $created->option_c ?? '',
                    'D' => $created->option_d ?? '',
                ],
                'correct_answer'  => match (strtoupper($created->correct_answer)) {
                    'OPTION_A', 'A' => 'A',
                    'OPTION_B', 'B' => 'B',
                    'OPTION_C', 'C' => 'C',
                    'OPTION_D', 'D' => 'D',
                    default => 'A',
                },
                'explanation'     => $created->explanation ?? '',
                'created_at'      => $created->created_at ?? now(),
            ],
        ], 201);
    }

    public function storeNationalExamQuestion(Request $request): JsonResponse
    {
        return $this->storeQuestion($request);
    }

    /**
     * Update an existing question.
     * PUT/PATCH /api/admin/national-exams/questions/{id}
     */
    public function updateQuestion(Request $request, $id): JsonResponse
    {
        $question = DB::table('national_exam_questions')->where('id', $id)->first();
        if (!$question) {
            return response()->json(['status' => 'error', 'message' => 'Question not found'], 404);
        }

        $updates = [];
        if ($request->has('question_text') || $request->has('question')) {
            $textVal = $request->input('question_text', $request->input('question'));
            if (Schema::hasColumn('national_exam_questions', 'question_text')) {
                $updates['question_text'] = $textVal;
            }
            if (Schema::hasColumn('national_exam_questions', 'question')) {
                $updates['question'] = $textVal;
            }
        }

        if ($request->has('options')) {
            $options = $request->input('options', []);
            if (isset($options['A'])) $updates['option_a'] = $options['A'];
            if (isset($options['B'])) $updates['option_b'] = $options['B'];
            if (isset($options['C'])) $updates['option_c'] = $options['C'];
            if (isset($options['D'])) $updates['option_d'] = $options['D'];
        } else {
            if ($request->has('option_a')) $updates['option_a'] = $request->input('option_a');
            if ($request->has('option_b')) $updates['option_b'] = $request->input('option_b');
            if ($request->has('option_c')) $updates['option_c'] = $request->input('option_c');
            if ($request->has('option_d')) $updates['option_d'] = $request->input('option_d');
        }

        if ($request->has('correct_answer')) {
            $rawCorrect = strtoupper(trim((string) $request->input('correct_answer')));
            $updates['correct_answer'] = match ($rawCorrect) {
                'A', 'OPTION_A' => 'option_a',
                'B', 'OPTION_B' => 'option_b',
                'C', 'OPTION_C' => 'option_c',
                'D', 'OPTION_D' => 'option_d',
                default => 'option_a',
            };
        }

        if ($request->has('diagram_url') || $request->has('image_url')) {
            $val = $request->input('diagram_url', $request->input('image_url'));
            if (Schema::hasColumn('national_exam_questions', 'image_url')) {
                $updates['image_url'] = $val;
            } elseif (Schema::hasColumn('national_exam_questions', 'diagram_url')) {
                $updates['diagram_url'] = $val;
            }
        }

        if ($request->has('explanation')) {
            $updates['explanation'] = $request->input('explanation');
        }

        if ($request->has('question_number') && Schema::hasColumn('national_exam_questions', 'question_number')) {
            $updates['question_number'] = (int) $request->input('question_number');
        }

        if (Schema::hasColumn('national_exam_questions', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            DB::table('national_exam_questions')->where('id', $id)->update($updates);
        }

        $fresh = DB::table('national_exam_questions')->where('id', $id)->first();

        // Record audit trail
        AuditLogService::log('exam_question.updated', 'App\Models\NationalExamQuestion', (array) $question, (array) $fresh);

        return response()->json([
            'status'  => 'success',
            'message' => 'Question updated successfully.',
            'data'    => [
                'id'              => $fresh->id,
                'exam_year_id'    => $fresh->national_exam_year_id ?? null,
                'subject_id'      => $fresh->national_exam_subject_id ?? null,
                'question_number' => (int) ($fresh->question_number ?? 1),
                'question_text'   => $fresh->question_text ?? ($fresh->question ?? ''),
                'diagram_url'     => $fresh->image_url ?? ($fresh->diagram_url ?? null),
                'options'         => [
                    'A' => $fresh->option_a ?? '',
                    'B' => $fresh->option_b ?? '',
                    'C' => $fresh->option_c ?? '',
                    'D' => $fresh->option_d ?? '',
                ],
                'correct_answer'  => match (strtoupper($fresh->correct_answer)) {
                    'OPTION_A', 'A' => 'A',
                    'OPTION_B', 'B' => 'B',
                    'OPTION_C', 'C' => 'C',
                    'OPTION_D', 'D' => 'D',
                    default => 'A',
                },
                'explanation'     => $fresh->explanation ?? '',
                'updated_at'      => $fresh->updated_at ?? now(),
            ],
        ]);
    }

    public function updateNationalExamQuestion(Request $request, $id): JsonResponse
    {
        return $this->updateQuestion($request, $id);
    }

    /**
     * Delete an individual question from MySQL.
     * DELETE /api/admin/national-exams/questions/{id}
     */
    public function destroyQuestion($id): JsonResponse
    {
        $question = DB::table('national_exam_questions')->where('id', $id)->first();
        if ($question) {
            DB::table('national_exam_questions')->where('id', $id)->delete();
            // Record audit trail
            AuditLogService::log('exam_question.deleted', 'App\Models\NationalExamQuestion', (array) $question, null);
        }
        return response()->json(['status' => 'success', 'message' => 'Question deleted successfully.']);
    }

    public function deleteNationalExamQuestion($id): JsonResponse
    {
        return $this->destroyQuestion($id);
    }

    /**
     * Batch import questions into a specific subject.
     * POST /api/admin/national-exams/questions/batch-import
     */
    public function batchImport(Request $request): JsonResponse
    {
        $yearId = (int) $request->input('exam_year_id', $request->input('national_exam_year_id'));
        $subjectId = (int) $request->input('subject_id', $request->input('national_exam_subject_id'));

        $subject = DB::table('national_exam_subjects')->where('id', $subjectId)->first();
        if (!$subject) {
            return response()->json(['status' => 'error', 'message' => 'Target subject not found'], 404);
        }

        $questionsList = $request->input('questions', []);
        if (!is_array($questionsList) || empty($questionsList)) {
            return response()->json(['status' => 'error', 'message' => 'No questions array provided'], 422);
        }

        $targetYearId = $yearId ?: ($subject->national_exam_year_id ?? 1);
        $insertedCount = 0;
        $formattedResults = [];

        DB::transaction(function () use ($subject, $targetYearId, $questionsList, &$insertedCount, &$formattedResults) {
            $currentMax = Schema::hasColumn('national_exam_questions', 'question_number')
                ? (DB::table('national_exam_questions')
                    ->where('national_exam_subject_id', $subject->id)
                    ->max('question_number') ?? 0)
                : 0;

            foreach ($questionsList as $idx => $q) {
                $options = $q['options'] ?? [];
                $optA = $options['A'] ?? ($options['a'] ?? ($q['option_a'] ?? ''));
                $optB = $options['B'] ?? ($options['b'] ?? ($q['option_b'] ?? ''));
                $optC = $options['C'] ?? ($options['c'] ?? ($q['option_c'] ?? ''));
                $optD = $options['D'] ?? ($options['d'] ?? ($q['option_d'] ?? ''));

                $rawCorrect = strtoupper(trim((string) ($q['correct_answer'] ?? 'A')));
                $correct = match ($rawCorrect) {
                    'A', 'OPTION_A' => 'option_a',
                    'B', 'OPTION_B' => 'option_b',
                    'C', 'OPTION_C' => 'option_c',
                    'D', 'OPTION_D' => 'option_d',
                    default => 'option_a',
                };

                $qNum = isset($q['question_number']) && $q['question_number'] > 0
                    ? (int) $q['question_number']
                    : ($currentMax + $idx + 1);

                $qText = $q['question_text'] ?? ($q['question'] ?? 'Question Text');
                $diagram = $q['diagram_url'] ?? ($q['image_url'] ?? null);
                $explanation = $q['explanation'] ?? '';

                $checkExistingQuery = DB::table('national_exam_questions')
                    ->where('national_exam_subject_id', $subject->id);

                if (Schema::hasColumn('national_exam_questions', 'question_number')) {
                    $checkExistingQuery->where('question_number', $qNum);
                }
                $existing = $checkExistingQuery->first();

                if ($existing) {
                    $updRow = [
                        'option_a'       => $optA,
                        'option_b'       => $optB,
                        'option_c'       => $optC,
                        'option_d'       => $optD,
                        'correct_answer' => $correct,
                        'explanation'    => $explanation,
                    ];
                    if (Schema::hasColumn('national_exam_questions', 'question_text')) {
                        $updRow['question_text'] = $qText;
                    }
                    if (Schema::hasColumn('national_exam_questions', 'question')) {
                        $updRow['question'] = $qText;
                    }
                    if (Schema::hasColumn('national_exam_questions', 'image_url')) {
                        $updRow['image_url'] = $diagram;
                    } elseif (Schema::hasColumn('national_exam_questions', 'diagram_url')) {
                        $updRow['diagram_url'] = $diagram;
                    }
                    if (Schema::hasColumn('national_exam_questions', 'updated_at')) {
                        $updRow['updated_at'] = now();
                    }
                    DB::table('national_exam_questions')->where('id', $existing->id)->update($updRow);
                    $qId = $existing->id;
                } else {
                    $insRow = [
                        'national_exam_subject_id' => $subject->id,
                        'national_exam_year_id'    => $targetYearId,
                        'national_exam_id'         => $subject->national_exam_id ?? 1,
                        'grade_id'                 => $subject->grade_id ?? ($subject->national_exam_id == 2 ? 5 : 3),
                        'option_a'                 => $optA,
                        'option_b'                 => $optB,
                        'option_c'                 => $optC,
                        'option_d'                 => $optD,
                        'correct_answer'           => $correct,
                        'explanation'              => $explanation,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ];

                    if (Schema::hasColumn('national_exam_questions', 'question_text')) {
                        $insRow['question_text'] = $qText;
                    }
                    if (Schema::hasColumn('national_exam_questions', 'question')) {
                        $insRow['question'] = $qText;
                    }
                    if (Schema::hasColumn('national_exam_questions', 'question_number')) {
                        $insRow['question_number'] = $qNum;
                    }
                    if (Schema::hasColumn('national_exam_questions', 'image_url')) {
                        $insRow['image_url'] = $diagram;
                    } elseif (Schema::hasColumn('national_exam_questions', 'diagram_url')) {
                        $insRow['diagram_url'] = $diagram;
                    }

                    $qId = DB::table('national_exam_questions')->insertGetId($insRow);
                }

                $insertedCount++;
                $formattedResults[] = [
                    'id'              => $qId,
                    'exam_year_id'    => $targetYearId,
                    'subject_id'      => $subject->id,
                    'subject_name'    => $subject->name,
                    'question_number' => $qNum,
                    'question_text'   => $qText,
                    'diagram_url'     => $diagram,
                    'options'         => [
                        'A' => $optA,
                        'B' => $optB,
                        'C' => $optC,
                        'D' => $optD,
                    ],
                    'correct_answer'  => match ($correct) {
                        'option_a' => 'A',
                        'option_b' => 'B',
                        'option_c' => 'C',
                        'option_d' => 'D',
                        default => 'A',
                    },
                    'explanation'     => $explanation,
                ];
            }
        });

        // Record audit trail
        AuditLogService::log('exam_questions.batch_imported', 'App\Models\NationalExamSubject', null, [
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'imported_count' => $insertedCount,
        ]);

        return response()->json([
            'status'         => 'success',
            'message'        => "Successfully imported {$insertedCount} questions into {$subject->name}.",
            'imported_count' => $insertedCount,
            'data'           => $formattedResults,
        ], 201);
    }

    public function batchImportQuestions(Request $request): JsonResponse
    {
        return $this->batchImport($request);
    }

    // ==========================================
    // 4. EXAM SUBJECT MANAGEMENT
    // ==========================================

    /**
     * Add an Exam Subject to a specific exam year.
     * POST /api/admin/national-exams/years/{yearId}/subjects
     * POST /api/admin/national-exam-subjects
     */
    public function storeSubject(Request $request, $yearId = null): JsonResponse
    {
        $targetYearId = $yearId ?: $request->input('national_exam_year_id', $request->input('year_id'));
        $year = DB::table('national_exam_years')->where('id', $targetYearId)->first();
        if (!$year) {
            return response()->json(['status' => 'error', 'message' => 'Exam year not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'stream' => 'nullable|in:natural,social,common',
            'stream_id' => 'nullable|integer',
        ]);

        $isGrade8 = ((int) $year->grade_id === 5) || ((int) $year->national_exam_id === 2);
        $streamId = null;
        $streamInput = 'common';

        if (!$isGrade8) {
            $streamInput = strtolower(trim((string) ($validated['stream'] ?? 'common')));
            if ($streamInput === 'natural' || (isset($validated['stream_id']) && (int) $validated['stream_id'] === 1)) {
                $streamId = 1;
                $streamInput = 'natural';
            } elseif ($streamInput === 'social' || (isset($validated['stream_id']) && (int) $validated['stream_id'] === 2)) {
                $streamId = 2;
                $streamInput = 'social';
            } else {
                $streamId = null;
                $streamInput = 'common';
            }
        }

        $insert = [
            'national_exam_year_id' => $year->id,
            'national_exam_id'      => $year->national_exam_id,
            'grade_id'              => $year->grade_id,
            'stream_id'             => $streamId,
            'name'                  => trim($validated['name']),
            'description'           => $request->filled('description') ? trim($request->input('description')) : trim($validated['name']) . ' Examination Paper',
            'created_at'            => now(),
            'updated_at'            => now(),
        ];

        if (Schema::hasColumn('national_exam_subjects', 'stream')) {
            $insert['stream'] = $streamInput;
        }
        if (Schema::hasColumn('national_exam_subjects', 'code') && !empty($validated['code'])) {
            $insert['code'] = strtoupper(trim($validated['code']));
        }

        $subjectId = DB::table('national_exam_subjects')->insertGetId($insert);

        if (class_exists(\App\Helpers\CurriculumCacheHelper::class)) {
            \App\Helpers\CurriculumCacheHelper::clearAllCurriculumCache();
        }

        $fresh = DB::table('national_exam_subjects')->where('id', $subjectId)->first();

        // Record audit trail
        AuditLogService::log('exam_subject.created', 'App\Models\NationalExamSubject', null, [
            'id' => $fresh->id,
            'name' => $fresh->name,
            'year_id' => $targetYearId,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Exam subject added successfully.',
            'data'    => [
                'id'              => $fresh->id,
                'name'            => $fresh->name,
                'code'            => strtoupper(substr($fresh->name, 0, 4)),
                'stream'          => $streamInput,
                'stream_id'       => $fresh->stream_id,
                'stream_name'     => $streamInput === 'natural' ? 'Natural Science' : ($streamInput === 'social' ? 'Social Science' : 'Common Core'),
                'questions_count' => 0,
            ],
        ], 201);
    }

    public function storeNationalExamSubject(Request $request): JsonResponse
    {
        return $this->storeSubject($request);
    }

    /**
     * Update subject stream or attributes.
     * PUT /api/admin/national-exams/subjects/{id}
     * PUT /api/admin/national-exam-subjects/{id}
     */
    public function updateSubject(Request $request, $id): JsonResponse
    {
        $subject = DB::table('national_exam_subjects')->where('id', $id)->first();
        if (!$subject) {
            return response()->json(['status' => 'error', 'message' => 'Subject not found.'], 404);
        }

        $isGrade8 = ((int) ($subject->grade_id ?? 0) === 5) || ((int) ($subject->national_exam_id ?? 0) === 2);
        $updates = [];
        $streamId = null;

        if ($isGrade8) {
            if (Schema::hasColumn('national_exam_subjects', 'stream_id')) {
                $updates['stream_id'] = null;
            }
            if (Schema::hasColumn('national_exam_subjects', 'stream')) {
                $updates['stream'] = 'common';
            }
        } elseif ($request->has('stream')) {
            $streamInput = strtolower(trim((string) $request->stream));
            if ($streamInput === 'natural') {
                $streamId = 1;
            } elseif ($streamInput === 'social') {
                $streamId = 2;
            } else {
                $streamId = null;
            }
            if (Schema::hasColumn('national_exam_subjects', 'stream_id')) {
                $updates['stream_id'] = $streamId;
            }
            if (Schema::hasColumn('national_exam_subjects', 'stream')) {
                $updates['stream'] = $streamInput;
            }
        } elseif ($request->has('stream_id')) {
            $sId = $request->stream_id;
            if ($sId === null || $sId === 'null' || $sId === '' || $sId === 0 || $sId === '0') {
                $streamId = null;
            } elseif ((int) $sId === 1 || (int) $sId === 2) {
                $streamId = (int) $sId;
            } else {
                $streamId = null;
            }
            if (Schema::hasColumn('national_exam_subjects', 'stream_id')) {
                $updates['stream_id'] = $streamId;
            }
        }

        if ($request->has('name')) {
            $updates['name'] = trim((string) $request->name);
        }
        if ($request->has('code') && Schema::hasColumn('national_exam_subjects', 'code')) {
            $updates['code'] = strtoupper(trim((string) $request->code));
        }
        if ($request->has('description') && Schema::hasColumn('national_exam_subjects', 'description')) {
            $updates['description'] = $request->description;
        }

        if (Schema::hasColumn('national_exam_subjects', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            DB::table('national_exam_subjects')->where('id', $id)->update($updates);
        }

        if (class_exists(\App\Helpers\CurriculumCacheHelper::class)) {
            \App\Helpers\CurriculumCacheHelper::clearAllCurriculumCache();
        }

        $fresh = DB::table('national_exam_subjects')->where('id', $id)->first();
        $streamType = $fresh->stream_id == 1 ? 'natural' : ($fresh->stream_id == 2 ? 'social' : 'common');

        // Record audit trail
        AuditLogService::log('exam_subject.updated', 'App\Models\NationalExamSubject', (array) $subject, (array) $fresh);

        return response()->json([
            'status'  => 'success',
            'message' => 'Exam subject updated successfully.',
            'data'    => [
                'id'          => $fresh->id,
                'name'        => $fresh->name,
                'code'        => strtoupper(substr($fresh->name, 0, 4)),
                'stream'      => $streamType,
                'stream_id'   => $fresh->stream_id,
                'stream_name' => $streamType === 'natural' ? 'Natural Science' : ($streamType === 'social' ? 'Social Science' : 'Common Core'),
            ],
        ]);
    }

    public function updateNationalExamSubject(Request $request, $id): JsonResponse
    {
        return $this->updateSubject($request, $id);
    }

    /**
     * Delete an Exam Subject and cascade delete its questions.
     * DELETE /api/admin/national-exams/subjects/{id}
     * DELETE /api/admin/national-exam-subjects/{id}
     */
    public function destroySubject($id): JsonResponse
    {
        $subject = DB::table('national_exam_subjects')->where('id', $id)->first();
        if (!$subject) {
            return response()->json(['status' => 'error', 'message' => 'Subject not found.'], 404);
        }

        DB::transaction(function () use ($id) {
            if (Schema::hasTable('national_exam_questions')) {
                DB::table('national_exam_questions')->where('national_exam_subject_id', $id)->delete();
            }
            DB::table('national_exam_subjects')->where('id', $id)->delete();
        });

        // Record audit trail
        AuditLogService::log('exam_subject.deleted', 'App\Models\NationalExamSubject', (array) $subject, null);

        if (class_exists(\App\Helpers\CurriculumCacheHelper::class)) {
            \App\Helpers\CurriculumCacheHelper::clearAllCurriculumCache();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Exam subject and its question papers deleted successfully.',
        ]);
    }

    public function deleteNationalExamSubject($id): JsonResponse
    {
        return $this->destroySubject($id);
    }

    private function resolveStudioGrade(Request $request): int
    {
        $grade = $request->integer('grade', 12);
        if ($grade <= 0) {
            preg_match('/\d+/', (string) $request->input('grade_level', 12), $m);
            $grade = !empty($m) ? (int) $m[0] : 12;
        }

        return in_array($grade, [8, 12], true) ? $grade : 12;
    }

    private function resolveStudioExams(int $grade, $examIdParam)
    {
        if ($examIdParam !== 'all' && is_numeric($examIdParam)) {
            $exam = DB::table('national_exams')->where('id', (int) $examIdParam)->first();

            return $exam ? collect([$exam]) : collect([]);
        }

        $gradeIds = Schema::hasTable('grades')
            ? DB::table('grades')->where('name', 'like', "%{$grade}%")->pluck('id')
            : collect();

        return DB::table('national_exams')
            ->where(function ($q) use ($grade, $gradeIds) {
                if ($gradeIds->isNotEmpty()) {
                    $q->whereIn('grade_id', $gradeIds);
                }
                if ($grade === 12) {
                    $q->orWhere('title', 'like', '%12%')
                      ->orWhere('title', 'like', '%Entrance%')
                      ->orWhere('title', 'like', '%EUEE%')
                      ->orWhere('title', 'like', '%ESSLCE%')
                      ->orWhere('title', 'like', '%Matric%');
                } else {
                    $q->orWhere('title', 'like', '%8%')
                      ->orWhere('title', 'like', '%Primary%')
                      ->orWhere('title', 'like', '%Ministry%');
                }
            })
            ->get();
    }

    private function filledYear($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $n = (int) $value;

        return $n > 0 ? $n : null;
    }

    private function resolveCalendarYears(object $row): array
    {
        $yearEcCol = $this->filledYear($row->year_ec ?? null);
        $yearGcCol = $this->filledYear($row->year_gc ?? null);
        $stored = $this->filledYear($row->year ?? null);

        if ($yearEcCol && $yearGcCol) {
            return [$yearEcCol, $yearGcCol];
        }
        if ($yearEcCol) {
            return [$yearEcCol, $yearEcCol + 8];
        }
        if ($yearGcCol) {
            return [$yearGcCol - 8, $yearGcCol];
        }
        if ($stored) {
            if ($stored > 2020) {
                return [$stored - 8, $stored];
            }

            return [$stored, $stored + 8];
        }

        return [2016, 2024];
    }

    private function inferStreamSlug(?int $streamId, string $name): string
    {
        if ($streamId === 1) {
            return 'natural';
        }
        if ($streamId === 2) {
            return 'social';
        }

        $lower = strtolower($name);
        if (preg_match('/physics|chemistry|biology|agriculture|information\s*tech|\bit\b/', $lower)) {
            return 'natural';
        }
        if (preg_match('/economics|geography|history/', $lower)) {
            return 'social';
        }

        return 'common';
    }

    private function streamLabel(string $slug): string
    {
        return match ($slug) {
            'natural' => 'Natural Science',
            'social'  => 'Social Science',
            default   => 'Common Core',
        };
    }

    private function shortExamTitle(string $title): string
    {
        $t = strtolower($title);
        if (str_contains($t, 'matric')) {
            return 'Matric';
        }
        if (str_contains($t, 'esslce') && str_contains($t, 'euee')) {
            return 'EUEE/ESSLCE';
        }
        if (str_contains($t, 'esslce')) {
            return 'ESSLCE';
        }
        if (str_contains($t, 'euee') || str_contains($t, 'entrance')) {
            return 'EUEE';
        }
        if (str_contains($t, 'primary') || str_contains($t, 'grade 8')) {
            return 'Grade 8';
        }

        return mb_substr($title !== '' ? $title : 'Exam', 0, 18);
    }

    private function emptyStudioStats(): array
    {
        return [
            'total_programs'  => 0,
            'total_subjects'  => 0,
            'total_years'     => 0,
            'total_questions' => 0,
        ];
    }
}
