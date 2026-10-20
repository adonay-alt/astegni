<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;

class TutorsController extends Controller
{
    /**
     * Format subjects array safely whether $subjects is a Collection, array, string, or null.
     *
     * @param mixed $subjects
     * @return array
     */
    protected function formatSubjects($subjects): array
    {
        if ($subjects instanceof Collection) {
            return $subjects->map(function ($s) {
                return [
                    'id' => is_object($s) ? $s->id : null,
                    'name' => is_object($s) ? ($s->title ?? $s->name ?? '') : (string) $s,
                    'title' => is_object($s) ? ($s->title ?? $s->name ?? '') : (string) $s,
                ];
            })->values()->all();
        }

        if (is_array($subjects)) {
            return array_values(array_map(function ($s) {
                if (is_array($s)) {
                    return [
                        'id' => $s['id'] ?? null,
                        'name' => $s['name'] ?? $s['title'] ?? '',
                        'title' => $s['title'] ?? $s['name'] ?? '',
                    ];
                }
                return ['id' => null, 'name' => (string) $s, 'title' => (string) $s];
            }, $subjects));
        }

        if (is_string($subjects) && trim($subjects) !== '') {
            $parts = array_map('trim', explode(',', $subjects));
            return array_map(fn($p) => ['id' => null, 'name' => $p, 'title' => $p], $parts);
        }

        return [];
    }

    /**
     * Format grades array safely whether $grades is a Collection, array, string, or null.
     *
     * @param mixed $grades
     * @return array
     */
    protected function formatGrades($grades): array
    {
        if ($grades instanceof Collection) {
            return $grades->map(function ($g) {
                return [
                    'id' => is_object($g) ? $g->id : null,
                    'name' => is_object($g) ? ($g->name ?? '') : (string) $g,
                ];
            })->values()->all();
        }

        if (is_array($grades)) {
            return array_values(array_map(function ($g) {
                if (is_array($g)) {
                    return [
                        'id' => $g['id'] ?? null,
                        'name' => $g['name'] ?? '',
                    ];
                }
                return ['id' => null, 'name' => (string) $g];
            }, $grades));
        }

        if (is_string($grades) && trim($grades) !== '') {
            $parts = array_map('trim', explode(',', $grades));
            return array_map(fn($p) => ['id' => null, 'name' => $p], $parts);
        }

        return [];
    }

    /**
     * Get subject string representation safely.
     *
     * @param mixed $subjects
     * @return string
     */
    protected function getSubjectString($subjects): string
    {
        if ($subjects instanceof Collection) {
            return (string) $subjects->pluck('title')->filter()->implode(', ');
        }

        if (is_array($subjects)) {
            $titles = array_filter(array_map(function ($s) {
                return is_array($s) ? ($s['title'] ?? $s['name'] ?? '') : (string) $s;
            }, $subjects));
            return implode(', ', $titles);
        }

        return is_string($subjects) ? $subjects : '';
    }

    /**
     * Get grade string representation safely.
     *
     * @param mixed $grades
     * @return string
     */
    protected function getGradeString($grades): string
    {
        if ($grades instanceof Collection) {
            return (string) $grades->pluck('name')->filter()->implode(', ');
        }

        if (is_array($grades)) {
            $names = array_filter(array_map(function ($g) {
                return is_array($g) ? ($g['name'] ?? '') : (string) $g;
            }, $grades));
            return implode(', ', $names);
        }

        return is_string($grades) ? $grades : '';
    }

    /**
     * Extract array of Subject IDs from payload (accepts IDs, arrays, objects, or comma-separated names/IDs).
     *
     * @param Request $request
     * @return array<int>
     */
    protected function extractSubjectIds(Request $request): array
    {
        $raw = null;
        foreach (['subjects', 'subject', 'subject_ids', 'subject_id', 'subject_names'] as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                if ($val !== null && $val !== '') {
                    $raw = $val;
                    break;
                }
            }
        }

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $trimmedStr = trim($raw);
            if (str_starts_with($trimmedStr, '[') || str_starts_with($trimmedStr, '{')) {
                $decoded = json_decode($trimmedStr, true);
                if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                    $raw = $decoded;
                }
            }
        }

        $items = [];
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw)) {
            $items = array_map('trim', explode(',', $raw));
        } else {
            $items = [$raw];
        }

        if (empty($items)) {
            return [];
        }

        $allSubjects = Subject::all();
        $subjectIds = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $val = $item['id'] ?? $item['title'] ?? $item['name'] ?? null;
            } else {
                $val = $item;
            }

            if ($val === null || trim((string) $val) === '') {
                continue;
            }

            $valStr = trim((string) $val);

            if (is_numeric($valStr)) {
                $num = (int) $valStr;
                $matchedById = $allSubjects->firstWhere('id', $num);
                if ($matchedById) {
                    $subjectIds[] = $matchedById->id;
                    continue;
                }
            }

            $matched = $allSubjects->first(function ($s) use ($valStr) {
                return strtolower($s->title) === strtolower($valStr);
            });

            if ($matched) {
                $subjectIds[] = $matched->id;
            }
        }

        return array_values(array_unique($subjectIds));
    }

    /**
     * Extract array of Grade IDs from payload (accepts IDs, arrays, objects, or comma-separated levels/names/IDs).
     *
     * @param Request $request
     * @return array<int>
     */
    protected function extractGradeIds(Request $request): array
    {
        $raw = null;
        foreach (['grades', 'grade', 'grade_ids', 'grade_id', 'grade_level', 'grade_levels', 'grade_name'] as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                if ($val !== null && $val !== '') {
                    $raw = $val;
                    break;
                }
            }
        }

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $trimmedStr = trim($raw);
            if (str_starts_with($trimmedStr, '[') || str_starts_with($trimmedStr, '{')) {
                $decoded = json_decode($trimmedStr, true);
                if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                    $raw = $decoded;
                }
            }
        }

        $items = [];
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw)) {
            $items = array_map('trim', explode(',', $raw));
        } else {
            $items = [$raw];
        }

        if (empty($items)) {
            return [];
        }

        $allGrades = Grade::all();
        $gradeIds = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $val = $item['id'] ?? $item['level'] ?? $item['name'] ?? null;
            } else {
                $val = $item;
            }

            if ($val === null || trim((string) $val) === '') {
                continue;
            }

            $valStr = trim((string) $val);

            if (is_numeric($valStr)) {
                $num = (int) $valStr;
                $matchedById = $allGrades->firstWhere('id', $num);
                if ($matchedById) {
                    $gradeIds[] = $matchedById->id;
                    continue;
                }
                $matchedByLevel = $allGrades->first(function ($g) use ($num) {
                    preg_match('/\d+/', $g->name, $m);
                    return !empty($m) && (int) $m[0] === $num;
                });
                if ($matchedByLevel) {
                    $gradeIds[] = $matchedByLevel->id;
                    continue;
                }
            }

            $matched = $allGrades->first(function ($g) use ($valStr) {
                return strtolower($g->name) === strtolower($valStr);
            });

            if ($matched) {
                $gradeIds[] = $matched->id;
                continue;
            }

            preg_match('/\d+/', $valStr, $matches);
            if (!empty($matches)) {
                $extractedNum = (int) $matches[0];
                $matchedByExtracted = $allGrades->first(function ($g) use ($extractedNum) {
                    preg_match('/\d+/', $g->name, $m);
                    return !empty($m) && (int) $m[0] === $extractedNum;
                });
                if ($matchedByExtracted) {
                    $gradeIds[] = $matchedByExtracted->id;
                }
            }
        }

        return array_values(array_unique($gradeIds));
    }

    /**
     * Format a TutorProfile model into a standardized public payload.
     *
     * @param TutorProfile $tutor
     * @return array
     */
    protected function formatPublicTutorPayload(TutorProfile $tutor): array
    {
        $subjectsCollection = $tutor->subjects instanceof Collection ? $tutor->subjects : collect();
        $gradesCollection = $tutor->grades instanceof Collection ? $tutor->grades : collect();
        $reviewsCollection = $tutor->reviews instanceof Collection ? $tutor->reviews : collect();

        $reviewCount = $reviewsCollection->count();
        $averageRating = $reviewCount > 0 ? round((float) $reviewsCollection->avg('rating'), 1) : 5.0;

        return [
            'id'             => $tutor->id,
            'user_id'        => $tutor->user_id,
            'name'           => $tutor->user?->name,
            'email'          => $tutor->user?->email,
            'phone'          => $tutor->phone,
            'image'          => $tutor->image_url ?? $tutor->image,
            'gender'         => $tutor->gender,
            'city'           => $tutor->city,
            'average_rating' => $averageRating,
            'rating'         => $averageRating,
            'review_count'   => $reviewCount,
            'total_reviews'  => $reviewCount,
            'status'         => $tutor->status,

            // 1. Comma-separated strings
            'subject'        => $subjectsCollection->pluck('title')->implode(', '),
            'grade'          => $gradesCollection->pluck('name')->implode(', '),

            // 2. Normalized Array of Objects (with both 'title' and 'name' properties)
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

            // 3. String arrays
            'subject_names'  => $subjectsCollection->pluck('title')->values(),
            'grade_names'    => $gradesCollection->pluck('name')->values(),

            // 4. Teachables object (matches frontend localStorage schema)
            'teachables'     => [
                'subjects' => $subjectsCollection->pluck('title')->values(),
                'grades'   => $gradesCollection->pluck('name')->values(),
            ],
        ];
    }

    /**
     * Return a list of tutors with eager loaded subjects and grades.
     * Supports filtering by status: ?status=pending|approved|rejected|all
     * Default for public requests without status is approved.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = TutorProfile::with(['user', 'reviews', 'subjects', 'grades']);

        $status = $request->query('status');
        if ($status && $status !== 'all') {
            $query->where('status', strtolower($status));
        } elseif ($status === 'all') {
            // Include all tutors across all statuses (pending, approved, rejected)
        } else {
            // Check if authenticated admin user
            $user = $request->user('sanctum');
            if (!$user || !$user->is_admin) {
                $query->where('status', 'approved');
            }
        }

        $tutors = $query->orderBy('created_at', 'desc')->get()->map(function ($tutor) {
            return $this->formatPublicTutorPayload($tutor);
        });

        return response()->json($tutors);
    }

    /**
     * Authenticated student applies to become a tutor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->tutorProfile()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor application already exists for this user.',
            ], 422);
        }

        $subjectIds = $this->extractSubjectIds($request);
        $gradeIds = $this->extractGradeIds($request);

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:50',
            'gender' => 'required|string|in:male,female',
            'city' => 'required|string|max:255',
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $validator->after(function ($validator) use ($subjectIds, $gradeIds) {
            if (empty($subjectIds)) {
                $validator->errors()->add('subject', 'At least one valid subject is required.');
            }
            if (empty($gradeIds)) {
                $validator->errors()->add('grade', 'At least one valid grade is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imagePath = $request->file('image')->store('tutors', 'public');

        $profile = DB::transaction(function () use ($user, $request, $imagePath, $subjectIds, $gradeIds) {
            $tutorProfile = TutorProfile::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'city' => $request->city,
                'image' => $imagePath,
                'status' => 'pending',
            ]);

            $tutorProfile->subjects()->sync($subjectIds);
            $tutorProfile->grades()->sync($gradeIds);

            return $tutorProfile;
        });

        $profile->load(['subjects', 'grades']);

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor application submitted successfully',
            'data' => $this->formatPublicTutorPayload($profile),
        ], 201);
    }

    /**
     * Return public profile of an approved tutor.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $tutor = TutorProfile::with(['user', 'reviews', 'subjects', 'grades'])
            ->where('status', 'approved')
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('user_id', $id);
            })
            ->first();

        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approved tutor not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatPublicTutorPayload($tutor),
        ]);
    }

    /**
     * Return reviews for an approved tutor.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getReviews(int $id): JsonResponse
    {
        $tutor = TutorProfile::where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('user_id', $id);
            })
            ->approved()
            ->first();

        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approved tutor not found',
            ], 404);
        }

        $reviews = TutorReview::with('student')
            ->where('tutor_profile_id', $tutor->id)
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'reviewer_name' => $review->student ? $review->student->name : 'Anonymous',
                    'rating' => (int) $review->rating,
                    'created_at' => $review->created_at ? $review->created_at->toIso8601String() : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $reviews,
        ]);
    }

    /**
     * Authenticated student posts or updates a rating for an approved tutor.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function storeReview(Request $request, int $id): JsonResponse
    {
        $tutor = TutorProfile::where('id', $id)->approved()->first();

        if (!$tutor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approved tutor not found',
            ], 404);
        }

        $student = $request->user();

        if ($tutor->user_id === $student->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot rate yourself',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review = TutorReview::updateOrCreate(
            [
                'tutor_profile_id' => $tutor->id,
                'student_id' => $student->id,
            ],
            [
                'rating' => (int) $request->rating,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Rating submitted successfully',
            'data' => [
                'id' => $review->id,
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'reviewer_name' => $student->name,
                'rating' => (int) $review->rating,
                'created_at' => $review->created_at ? $review->created_at->toIso8601String() : null,
            ],
        ], 201);
    }

    /**
     * Authenticated tutor updates their profile.
     * Resets status to pending for admin re-approval.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $tutorProfile = $user->tutorProfile;

        if (!$tutorProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor profile not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'sometimes|required|string|max:50',
            'gender' => 'sometimes|required|string|in:male,female',
            'city' => 'sometimes|required|string|max:255',
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dataToUpdate = $request->only(['phone', 'gender', 'city']);

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('tutors', 'public');

            if ($tutorProfile->image && !str_starts_with($tutorProfile->image, 'http://') && !str_starts_with($tutorProfile->image, 'https://')) {
                if (Storage::disk('public')->exists($tutorProfile->image)) {
                    Storage::disk('public')->delete($tutorProfile->image);
                }
            }

            $dataToUpdate['image'] = $newPath;
        }

        // Set status to pending for admin re-approval
        $dataToUpdate['status'] = 'pending';

        $subjectIds = $this->extractSubjectIds($request);
        $gradeIds = $this->extractGradeIds($request);

        DB::transaction(function () use ($tutorProfile, $dataToUpdate, $subjectIds, $gradeIds) {
            $tutorProfile->update($dataToUpdate);

            if (!empty($subjectIds)) {
                $tutorProfile->subjects()->sync($subjectIds);
            }
            if (!empty($gradeIds)) {
                $tutorProfile->grades()->sync($gradeIds);
            }
        });

        $tutorProfile->refresh();
        $tutorProfile->load(['subjects', 'grades']);

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor profile updated successfully. Profile status set to pending for review.',
            'data' => $this->formatPublicTutorPayload($tutorProfile),
        ]);
    }

    /**
     * Return available subjects from the subjects table.
     *
     * @return JsonResponse
     */
    public function getSubjects(): JsonResponse
    {
        $subjects = Subject::select('id', 'title', 'description')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor subjects retrieved successfully',
            'data' => $subjects,
        ]);
    }

    /**
     * Return available grades from the grades table.
     *
     * @return JsonResponse
     */
    public function getGrades(): JsonResponse
    {
        $grades = Grade::select('id', 'name', 'type')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor grades retrieved successfully',
            'data' => $grades,
        ]);
    }

    /**
     * Get authenticated user's tutor application/profile status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getApplicationStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $tutorProfile = $user->tutorProfile()->first();

        if (!$tutorProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor application not found',
            ], 404, [
                'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        // Retrieve the latest tutor application status notification
        $latestNotification = Notification::forUser($user->id)
            ->where('type', 'tutor_application')
            ->latest()
            ->first();

        $reason = $latestNotification?->data['reason'] ?? null;
        $feedback = $latestNotification?->data['feedback'] ?? null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'application_status' => $tutorProfile->status,
                'status' => $tutorProfile->status,
                'application_id' => $tutorProfile->id,
                'submitted_date' => $tutorProfile->created_at ? $tutorProfile->created_at->toIso8601String() : null,
                'approval_date' => ($tutorProfile->status === 'approved' && $tutorProfile->updated_at) ? $tutorProfile->updated_at->toIso8601String() : null,
                'rejection_date' => ($tutorProfile->status === 'rejected' && $tutorProfile->updated_at) ? $tutorProfile->updated_at->toIso8601String() : null,
                'rejection_reason' => $reason,
                'rejection_feedback' => $feedback,
                'reason' => $reason,
                'feedback' => $feedback,
                'notification' => $latestNotification,
            ],
            'application_status' => $tutorProfile->status,
            'status' => $tutorProfile->status,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Delete/remove ONLY authenticated user's tutor profile while keeping user and student account intact.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $tutorProfile = $user->tutorProfile()->first();

        if (!$tutorProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tutor profile not found',
            ], 404);
        }

        if ($tutorProfile->image && !str_starts_with($tutorProfile->image, 'http://') && !str_starts_with($tutorProfile->image, 'https://')) {
            if (Storage::disk('public')->exists($tutorProfile->image)) {
                Storage::disk('public')->delete($tutorProfile->image);
            }
        }

        $tutorProfile->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tutor profile removed successfully',
        ], 200);
    }

    /**
     * Get notifications for tutor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $notifications = Notification::forUser($user->id)
            ->latest()
            ->get();

        $unreadCount = Notification::forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Delete the authenticated user's account along with associated profiles and files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully',
        ], 200);
    }
}
