<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\TutorProfile;
use App\Models\TutorReview;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AdminTutorController extends Controller
{
    /**
     * Authorize admin or designated operational staff roles.
     */
    private function authorizeAdminOrStaff(Request $request): ?JsonResponse
    {
        $user = $request->user();
        $allowedRoles = ['super_admin', 'tutor_moderator', 'support_moderator', 'academic_editor', 'support_agent', 'tutor'];
        $userRole = strtolower(trim((string)$user?->role));

        if (!$user || (empty($user->is_admin) && !in_array($userRole, $allowedRoles, true))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Staff privileges required.',
            ], 403);
        }

        return null;
    }

    /**
     * Get list of all tutor profiles for admin verification queue.
     * Supports filtering by status: ?status=pending|approved|rejected|all
     * Supports search: ?q=name,phone,city
     * Supports grade and subject filtering: ?grade_id=, ?subject_id=
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if ($authError = $this->authorizeAdminOrStaff($request)) {
            return $authError;
        }

        $user = $request->user();

        // 1. Calculate real database counts across all statuses
        $counts = [
            'total'    => DB::table('tutor_profiles')->count(),
            'all'      => DB::table('tutor_profiles')->count(),
            'pending'  => DB::table('tutor_profiles')->whereRaw('LOWER(status) = ?', ['pending'])->count(),
            'approved' => DB::table('tutor_profiles')->whereRaw('LOWER(status) = ?', ['approved'])->count(),
            'rejected' => DB::table('tutor_profiles')->whereRaw('LOWER(status) = ?', ['rejected'])->count(),
        ];

        // 2. Query tutors with filters
        $query = TutorProfile::with([
            'user:id,first_name,last_name,email',
            'grades:id,name',
            'subjects:id,title',
            'reviews:id,tutor_profile_id,rating',
        ]);

        if ($request->filled('status') && $request->status !== 'all') {
            $status = strtolower(trim($request->status));
            $query->whereRaw('LOWER(status) = ?', [$status]);
        }

        if ($request->filled('subject_id') && is_numeric($request->subject_id)) {
            $query->whereHas('subjects', function ($sub) use ($request) {
                $sub->where('subjects.id', (int) $request->subject_id);
            });
        }

        if ($request->filled('grade_id') && is_numeric($request->grade_id)) {
            $query->whereHas('grades', function ($sub) use ($request) {
                $sub->where('grades.id', (int) $request->grade_id);
            });
        }

        if ($request->filled('q')) {
            $searchTerm = trim($request->q);
            $query->where(function ($sub) use ($searchTerm) {
                $sub->where('phone', 'like', "%{$searchTerm}%")
                    ->orWhere('city', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', "%{$searchTerm}%")
                          ->orWhere('last_name', 'like', "%{$searchTerm}%")
                          ->orWhere('email', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $tutors = $query->orderBy('id', 'desc')->get()->map(function ($tutor) {
            $subjectsCollection = $tutor->subjects instanceof \Illuminate\Support\Collection ? $tutor->subjects : collect();
            $gradesCollection = $tutor->grades instanceof \Illuminate\Support\Collection ? $tutor->grades : collect();
            $reviewsCollection = $tutor->reviews instanceof \Illuminate\Support\Collection ? $tutor->reviews : collect();

            // Dynamically calculate rating & review count safely
            $reviewCount = $reviewsCollection->count();
            $averageRating = $reviewCount > 0 ? round((float) $reviewsCollection->avg('rating'), 1) : 5.0;

            return [
                'id'             => $tutor->id,
                'user_id'        => $tutor->user_id,
                'user'           => $tutor->user,
                'name'           => $tutor->user?->name ?? 'Tutor Applicant',
                'email'          => $tutor->user?->email ?? '',
                'phone'          => $tutor->phone ?? '',
                'image'          => $tutor->image_url ?? $tutor->image,
                'avatar_url'     => $tutor->image_url ?? $tutor->image,
                'gender'         => $tutor->gender ?? 'other',
                'city'           => $tutor->city ?? 'Addis Ababa',
                'average_rating' => $averageRating,
                'rating'         => $averageRating,
                'review_count'   => $reviewCount,
                'total_reviews'  => $reviewCount,
                'status'         => $tutor->status,
                'subject'        => $subjectsCollection->pluck('title')->implode(', '),
                'grade'          => $gradesCollection->pluck('name')->implode(', '),
                'subject_names'  => $subjectsCollection->pluck('title')->values(),
                'grade_names'    => $gradesCollection->pluck('name')->values(),
                'subjects'       => $subjectsCollection->map(fn($s) => [
                    'id'    => $s->id,
                    'title' => $s->title ?? $s->name,
                    'name'  => $s->title ?? $s->name,
                ])->values(),
                'grades'         => $gradesCollection->map(fn($g) => [
                    'id'    => $g->id,
                    'name'  => $g->name,
                    'level' => $g->name,
                ])->values(),
                'documents'      => [],
                'created_at'     => $tutor->created_at?->toISOString() ?? now()->toISOString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $tutors,
            'counts' => $counts,
            'stats'  => $counts, // Support both keys
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get detailed applicant dossier including verification documents and uploaded photo URL.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($authError = $this->authorizeAdminOrStaff($request)) {
            return $authError;
        }

        $user = $request->user();

        $tutor = TutorProfile::with(['user', 'reviews.student', 'subjects', 'grades'])->find($id);

        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor profile not found',
            ], 404);
        }

        $subjectsCollection = $tutor->subjects instanceof \Illuminate\Support\Collection ? $tutor->subjects : collect();
        $gradesCollection = $tutor->grades instanceof \Illuminate\Support\Collection ? $tutor->grades : collect();
        $reviewsCollection = $tutor->reviews instanceof \Illuminate\Support\Collection ? $tutor->reviews : collect();

        $reviewCount = $reviewsCollection->count();
        $averageRating = $reviewCount > 0 ? round((float) $reviewsCollection->avg('rating'), 1) : 5.0;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $tutor->id,
                'user_id'        => $tutor->user_id,
                'name'           => $tutor->user?->name ?? 'Tutor Applicant',
                'email'          => $tutor->user?->email ?? '',
                'phone'          => $tutor->phone ?? '',
                'image'          => $tutor->image_url ?? $tutor->image,
                'avatar_url'     => $tutor->image_url ?? $tutor->image,
                'gender'         => $tutor->gender ?? 'other',
                'city'           => $tutor->city ?? 'Addis Ababa',
                'average_rating' => $averageRating,
                'rating'         => $averageRating,
                'review_count'   => $reviewCount,
                'total_reviews'  => $reviewCount,
                'status'         => $tutor->status,
                'subject'        => $subjectsCollection->pluck('title')->implode(', '),
                'grade'          => $gradesCollection->pluck('name')->implode(', '),
                'subject_names'  => $subjectsCollection->pluck('title')->values(),
                'grade_names'    => $gradesCollection->pluck('name')->values(),
                'subjects'       => $subjectsCollection->map(fn($s) => [
                    'id'    => $s->id,
                    'title' => $s->title ?? $s->name,
                    'name'  => $s->title ?? $s->name,
                ])->values(),
                'grades'         => $gradesCollection->map(fn($g) => [
                    'id'    => $g->id,
                    'name'  => $g->name,
                    'level' => $g->name,
                ])->values(),
                'documents'      => [],
                'reviews'        => $tutor->reviews->map(fn($r) => [
                    'id'            => $r->id,
                    'reviewer_name' => $r->student?->name ?? 'Student',
                    'rating'        => (int) $r->rating,
                    'review'        => $r->review,
                    'status'        => $r->status ?? 'approved',
                    'created_at'    => $r->created_at?->toISOString(),
                ]),
                'created_at'     => $tutor->created_at?->toISOString() ?? now()->toISOString(),
            ],
        ]);
    }

    /**
     * Update tutor profile approval status.
     * Validates status ('approved' | 'rejected' | 'suspended').
     * When rejecting: Strictly expects and validates reason: 'Inappropriate picture in tutor profile'.
     * Dispatches approval notification or records rejection feedback.
     * Logs action to admin_audit_logs.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        if ($authError = $this->authorizeAdminOrStaff($request)) {
            return $authError;
        }

        $user = $request->user();

        $tutor = TutorProfile::with(['user', 'subjects', 'grades'])->find($id);

        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor profile not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:approved,rejected,suspended,pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $oldStatus = $tutor->status;
        $newStatus = $request->status;

        $reason = $request->input('reason', $request->input('rejection_reason'));
        $feedback = $request->input('feedback', $request->input('message'));

        // Strict rejection reason enforcement
        if ($newStatus === 'rejected') {
            if ($reason !== 'Inappropriate picture in tutor profile') {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Rejection reason must strictly be: 'Inappropriate picture in tutor profile'.",
                    'errors'  => [
                        'reason' => ["Rejection reason must strictly be: 'Inappropriate picture in tutor profile'."],
                    ],
                ], 422);
            }
        }

        DB::transaction(function () use ($tutor, $oldStatus, $newStatus, $reason, $feedback) {
            $tutor->update([
                'status' => $newStatus,
            ]);

            // Synchronize user role with tutor approval status
            if ($newStatus === 'approved') {
                $tutor->user?->update(['role' => 'tutor']);
            } elseif ($newStatus === 'rejected' && $tutor->user?->role === 'tutor') {
                $tutor->user?->update(['role' => 'student']);
            }

            // Only generate notification on an actual status change
            if ($oldStatus !== $newStatus) {
                if ($newStatus === 'approved') {
                    Notification::create([
                        'user_id' => $tutor->user_id,
                        'type'    => 'tutor_application',
                        'title'   => 'Tutor Application Accepted',
                        'message' => 'Congratulations! Your tutor application has been approved. You can now access your tutor features.',
                        'data'    => [
                            'tutor_profile_id' => $tutor->id,
                            'status'           => 'approved',
                            'action'           => 'approved',
                            'timestamp'        => now()->toISOString(),
                        ],
                    ]);
                } elseif ($newStatus === 'rejected') {
                    $rejectionMessage = $feedback ?: "Your tutor application was rejected: {$reason}";

                    Notification::create([
                        'user_id' => $tutor->user_id,
                        'type'    => 'tutor_application',
                        'title'   => 'Tutor Application Rejected',
                        'message' => $rejectionMessage,
                        'data'    => [
                            'tutor_profile_id' => $tutor->id,
                            'status'           => 'rejected',
                            'action'           => 'rejected',
                            'reason'           => $reason,
                            'feedback'         => $feedback,
                            'timestamp'        => now()->toISOString(),
                        ],
                    ]);
                }
            }
        });

        // Record in Admin Audit Log
        try {
            AdminAuditLog::record(
                adminId: $user->id,
                action: 'tutor.' . $newStatus,
                auditableType: TutorProfile::class,
                auditableId: $tutor->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status'   => $newStatus,
                    'reason'   => $reason,
                    'feedback' => $feedback,
                ]
            );
        } catch (\Throwable $e) {}

        $tutor->refresh();
        $tutor->load(['subjects', 'grades']);

        return response()->json([
            'status'  => 'success',
            'message' => "Tutor status updated to {$request->status}",
            'data'    => [
                'id'             => $tutor->id,
                'user_id'        => $tutor->user_id,
                'name'           => $tutor->user ? $tutor->user->name : null,
                'email'          => $tutor->user ? $tutor->user->email : null,
                'phone'          => $tutor->phone,
                'gender'         => $tutor->gender,
                'city'           => $tutor->city,
                'image'          => $tutor->image_url ?? $tutor->image,
                'avatar_url'     => $tutor->image_url ?? $tutor->image,
                'status'         => $tutor->status,
                'subject'        => $tutor->subjects?->pluck('title')?->implode(', ') ?? '',
                'grade'          => $tutor->grades?->pluck('name')?->implode(', ') ?? '',
                'subjects'       => $tutor->subjects ? $tutor->subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->title, 'title' => $s->title])->values() : [],
                'grades'         => $tutor->grades ? $tutor->grades->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->values() : [],
            ],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Batch update multiple tutor profiles approval status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        if ($authError = $this->authorizeAdminOrStaff($request)) {
            return $authError;
        }

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'required|integer',
            'status' => 'required|string|in:approved,rejected,pending,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $newStatus = $request->status;
        $reason = $request->input('reason', $request->input('rejection_reason'));
        $feedback = $request->input('feedback', $request->input('message'));

        if ($newStatus === 'rejected' && $reason !== 'Inappropriate picture in tutor profile') {
            return response()->json([
                'status'  => 'error',
                'message' => "Rejection reason must strictly be: 'Inappropriate picture in tutor profile'.",
                'errors'  => [
                    'reason' => ["Rejection reason must strictly be: 'Inappropriate picture in tutor profile'."],
                ],
            ], 422);
        }

        $tutors = TutorProfile::with(['user'])->whereIn('id', $request->ids)->get();
        $updatedCount = 0;

        DB::transaction(function () use ($tutors, $newStatus, $reason, $feedback, &$updatedCount, $user) {
            foreach ($tutors as $tutor) {
                $oldStatus = $tutor->status;

                $tutor->update([
                    'status' => $newStatus,
                ]);

                if ($newStatus === 'approved') {
                    $tutor->user?->update(['role' => 'tutor']);
                } elseif ($newStatus === 'rejected' && $tutor->user?->role === 'tutor') {
                    $tutor->user?->update(['role' => 'student']);
                }

                if ($oldStatus !== $newStatus) {
                    if ($newStatus === 'approved') {
                        Notification::create([
                            'user_id' => $tutor->user_id,
                            'type'    => 'tutor_application',
                            'title'   => 'Tutor Application Accepted',
                            'message' => 'Congratulations! Your tutor application has been approved. You can now access your tutor features.',
                            'data'    => [
                                'tutor_profile_id' => $tutor->id,
                                'status'           => 'approved',
                                'action'           => 'approved',
                                'timestamp'        => now()->toISOString(),
                            ],
                        ]);
                    } elseif ($newStatus === 'rejected') {
                        $rejectionMessage = $feedback ?: "Your tutor application was rejected: {$reason}";

                        Notification::create([
                            'user_id' => $tutor->user_id,
                            'type'    => 'tutor_application',
                            'title'   => 'Tutor Application Rejected',
                            'message' => $rejectionMessage,
                            'data'    => [
                                'tutor_profile_id' => $tutor->id,
                                'status'           => 'rejected',
                                'action'           => 'rejected',
                                'reason'           => $reason,
                                'feedback'         => $feedback,
                                'timestamp'        => now()->toISOString(),
                            ],
                        ]);
                    }
                }

                try {
                    AdminAuditLog::record(
                        adminId: $user->id,
                        action: 'tutor.' . $newStatus,
                        auditableType: TutorProfile::class,
                        auditableId: $tutor->id,
                        oldValues: ['status' => $oldStatus],
                        newValues: [
                            'status'   => $newStatus,
                            'reason'   => $reason,
                            'feedback' => $feedback,
                        ]
                    );
                } catch (\Throwable $e) {}

                $updatedCount++;
            }
        });

        return response()->json([
            'status'        => 'success',
            'message'       => "Successfully updated {$updatedCount} tutor profiles to {$newStatus}.",
            'updated_count' => $updatedCount,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get list of tutor ratings and reviews for moderation.
     * Supports filtering by status: ?status=pending|approved|rejected|all
     * Supports search: ?q=
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reviews(Request $request): JsonResponse
    {
        if ($authError = $this->authorizeAdminOrStaff($request)) {
            return $authError;
        }

        // Query tutor reviews directly with fallback to table query
        $query = DB::table('tutor_reviews')
            ->leftJoin('tutor_profiles', 'tutor_reviews.tutor_profile_id', '=', 'tutor_profiles.id')
            ->leftJoin('users as tutor_users', 'tutor_profiles.user_id', '=', 'tutor_users.id')
            ->leftJoin('users as student_users', function ($join) {
                // Check student_id or user_id
                $join->on('tutor_reviews.student_id', '=', 'student_users.id');
            })
            ->select([
                'tutor_reviews.*',
                'tutor_users.first_name as tutor_first_name',
                'tutor_users.last_name as tutor_last_name',
                'tutor_users.email as tutor_email',
                'tutor_profiles.image as tutor_image',
                'student_users.first_name as student_first_name',
                'student_users.last_name as student_last_name',
                'student_users.email as student_email',
            ]);

        // Optional rating filter (1 to 5 stars)
        if ($request->filled('rating') && is_numeric($request->rating)) {
            $query->where('tutor_reviews.rating', (int) $request->rating);
        }

        // Search term
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('tutor_users.first_name', 'like', "%{$q}%")
                    ->orWhere('tutor_users.last_name', 'like', "%{$q}%")
                    ->orWhere('tutor_users.email', 'like', "%{$q}%")
                    ->orWhere('student_users.first_name', 'like', "%{$q}%")
                    ->orWhere('student_users.last_name', 'like', "%{$q}%")
                    ->orWhere('student_users.email', 'like', "%{$q}%")
                    ->orWhere('tutor_reviews.review', 'like', "%{$q}%");
            });
        }

        $reviews = $query->orderBy('tutor_reviews.id', 'desc')->get()->map(function ($rev) {
            $tutorName = trim("{$rev->tutor_first_name} {$rev->tutor_last_name}") ?: 'Tutor';
            $studentName = trim("{$rev->student_first_name} {$rev->student_last_name}") ?: 'Student Reviewer';

            return [
                'id'            => $rev->id,
                'tutor_id'      => $rev->tutor_profile_id ?? null,
                'tutor_name'    => $tutorName,
                'tutor_email'   => $rev->tutor_email ?? '',
                'tutor_image'   => $rev->tutor_image ?? null,
                'subject'       => 'General Academic',
                'reviewer_name' => $studentName,
                'reviewer_email'=> $rev->student_email ?? '',
                'rating'        => (int) ($rev->rating ?? 5),
                'review'        => $rev->review ?? '',
                'status'        => $rev->status ?? 'approved',
                'created_at'    => $rev->created_at ?? now()->toISOString(),
            ];
        });

        $totalReviews = DB::table('tutor_reviews')->count();
        $avgRating = $totalReviews > 0 ? round((float) DB::table('tutor_reviews')->avg('rating'), 1) : 0.0;
        $fiveStarCount = DB::table('tutor_reviews')->where('rating', 5)->count();

        return response()->json([
            'status' => 'success',
            'data'   => $reviews,
            'stats'  => [
                'total'           => $totalReviews,
                'average_rating'  => $avgRating,
                'five_star_count' => $fiveStarCount,
            ],
            'counts' => [
                'total'    => $totalReviews,
                'all'      => $totalReviews,
                'approved' => $totalReviews,
                'pending'  => 0,
                'rejected' => 0,
            ],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    /**
     * Moderate a tutor review (approve or reject).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateReviewStatus(Request $request, int $id): JsonResponse
    {
        if ($authError = $this->authorizeAdminOrStaff($request)) {
            return $authError;
        }

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:approved,rejected,pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $review = TutorReview::find($id);

        if (!$review) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tutor review not found',
            ], 404);
        }

        $oldStatus = $review->status ?? 'approved';
        $newStatus = $request->status;

        if (Schema::hasColumn('tutor_reviews', 'status')) {
            $review->update(['status' => $newStatus]);
        }

        try {
            AdminAuditLog::record(
                adminId: $user->id,
                action: 'tutor_review.' . $newStatus,
                auditableType: TutorReview::class,
                auditableId: $review->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus]
            );
        } catch (\Throwable $e) {}

        return response()->json([
            'status'  => 'success',
            'message' => "Tutor review status updated to {$newStatus}",
            'data'    => [
                'id'         => $review->id,
                'status'     => $newStatus,
                'updated_at' => now()->toISOString(),
            ],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }
}
