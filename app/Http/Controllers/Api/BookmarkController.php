<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookmarkController extends Controller
{
    /**
     * Get all bookmarked chapters for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookmarks = Bookmark::with(['chapter.subject', 'chapter.grade'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Bookmarks retrieved successfully',
            'data' => $bookmarks,
        ], 200);
    }

    /**
     * Bookmark a chapter for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $chapterId = (int) $request->input('chapter_id');

        $existing = Bookmark::where('user_id', $user->id)
            ->where('chapter_id', $chapterId)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter is already bookmarked.',
            ], 422);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'chapter_id' => $chapterId,
        ]);

        $bookmark->load(['chapter.subject', 'chapter.grade']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chapter bookmarked successfully',
            'data' => $bookmark,
        ], 201);
    }

    /**
     * Remove a bookmarked chapter for the authenticated user.
     *
     * @param Request $request
     * @param int|string $chapterId
     * @return JsonResponse
     */
    public function destroy(Request $request, $chapterId): JsonResponse
    {
        $user = $request->user();

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('chapter_id', $chapterId)
            ->first();

        if (!$bookmark) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bookmark not found',
            ], 404);
        }

        $bookmark->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bookmark removed successfully',
        ], 200);
    }
}
