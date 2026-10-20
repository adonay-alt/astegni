<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\ChapterProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChapterProgressController extends Controller
{
    /**
     * Get all chapter progress records for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $progress = ChapterProgress::with(['chapter.subject', 'chapter.grade'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Chapter progress retrieved successfully',
            'data' => $progress,
        ], 200);
    }

    /**
     * Save or update chapter completion progress for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chapter_id' => ['required', 'integer', 'exists:chapters,id'],
            'completed' => ['required', 'boolean'],
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
        $completed = (bool) $request->input('completed');

        $record = ChapterProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'chapter_id' => $chapterId,
            ],
            [
                'completed' => $completed,
                'completed_at' => $completed ? now() : null,
            ]
        );

        $record->load(['chapter.subject', 'chapter.grade']);

        return response()->json([
            'status' => 'success',
            'message' => 'Chapter progress updated successfully',
            'data' => $record,
        ], 200);
    }
}
