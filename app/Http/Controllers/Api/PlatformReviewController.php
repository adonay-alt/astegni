<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller; 
use App\Models\PlatformReview;
use App\Models\AdminAuditLog;
use App\Enums\PlatformReviewStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlatformReviewController extends Controller
{
    /**
     * Display approved platform reviews on the public frontend.
     * Includes the user's profile image URL if uploaded.
     * Respects ?limit= query parameter (defaults to 10).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }

        $reviews = PlatformReview::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name')
                    ->with(['studentProfile:id,user_id,photo', 'tutorProfile:id,user_id,image']);
            }])
            ->approved()
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($review) {
                $data = $review->toArray();
                if ($review->user) {
                    $data['user']['profile_photo_url'] = $review->user->profile_photo_url;
                    $data['user']['photo_url'] = $review->user->profile_photo_url;
                }
                return $data;
            });

        return response()->json(['status' => 'success', 'data' => $reviews], 200);
    }

    /**
     * Authenticated user submits a platform review.
     * Default status is set strictly to 'pending' for admin approval.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review = PlatformReview::create([
            'user_id' => $request->user()->id,
            'rating' => (int) $request->rating,
            'comment' => $request->comment,
            'status' => PlatformReviewStatus::PENDING,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully. It will be visible on the platform after admin approval.',
            'data' => $review,
        ], 201);
    }

    /**
     * Display all platform reviews (pending, approved, rejected) for admin management.
     * Includes reviewer profile image details.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        $reviews = PlatformReview::with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email')
                    ->with([
                        'studentProfile' => function ($sp) {
                            $sp->select('id', 'user_id', 'grade_id', 'photo')->with('grade:id,name');
                        },
                        'tutorProfile:id,user_id,image',
                    ]);
            }])
            ->latest()
            ->get()
            ->map(function ($review) {
                $data = $review->toArray();
                if ($review->user) {
                    $data['user']['profile_photo_url'] = $review->user->profile_photo_url;
                    $data['user']['photo_url'] = $review->user->profile_photo_url;
                }
                return $data;
            });

        return response()->json(['status' => 'success', 'data' => $reviews], 200);
    }

    /**
     * Admin updates approval status of a platform review.
     * Status in database must strictly be pending, approved, or rejected only.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        $review = PlatformReview::with(['user' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'email')
                ->with([
                    'studentProfile' => function ($sp) {
                        $sp->select('id', 'user_id', 'grade_id', 'photo')->with('grade:id,name');
                    },
                    'tutorProfile:id,user_id,image',
                ]);
        }])->find($id);

        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Platform review not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::enum(PlatformReviewStatus::class)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed. Status must strictly be pending, approved, or rejected.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $review->status instanceof \BackedEnum ? $review->status->value : $review->status;
        $review->update(['status' => $request->status]);

        // Record in Admin Audit Log
        try {
            AdminAuditLog::record(
                adminId: $user->id,
                action: 'platform_review.' . $request->status,
                auditableType: PlatformReview::class,
                auditableId: $review->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $request->status]
            );
        } catch (\Throwable $e) {}

        $data = $review->toArray();
        if ($review->user) {
            $data['user']['profile_photo_url'] = $review->user->profile_photo_url;
            $data['user']['photo_url'] = $review->user->profile_photo_url;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Platform review status updated to {$request->status}",
            'data' => $data,
        ], 200);
    }

    /**
     * Admin deletes a platform review.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        $review = PlatformReview::find($id);

        if (!$review) {
            return response()->json([
                'status' => 'error',
                'message' => 'Platform review not found',
            ], 404);
        }

        $oldValues = [
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status instanceof \BackedEnum ? $review->status->value : $review->status,
        ];

        $review->delete();

        // Record in Admin Audit Log
        try {
            AdminAuditLog::record(
                adminId: $user->id,
                action: 'platform_review.deleted',
                auditableType: PlatformReview::class,
                auditableId: $id,
                oldValues: $oldValues,
                newValues: null
            );
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Platform review deleted successfully',
        ], 200);
    }
}