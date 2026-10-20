<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    /**
     * Display a listing of videos with optional filtering by grade_id, subject_id, or chapter_id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Video::with(['grade', 'subject', 'chapter']);

        if ($request->has('grade_id') && $request->grade_id !== null) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->has('subject_id') && $request->subject_id !== null) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('chapter_id') && $request->chapter_id !== null) {
            $query->where('chapter_id', $request->chapter_id);
        }

        $videos = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Videos retrieved successfully',
            'data' => $videos,
        ]);
    }

    /**
     * Display the specified video.
     *
     * @param int|string $id
     * @return JsonResponse
     */
     
     
     public function count(): JsonResponse
{
    $count = Video::whereNotNull('video_url')->count();

    return response()->json([
        'status' => 'success',
        'count' => $count,
    ]);
}
    public function show($id): JsonResponse
    {
        $video = Video::with(['grade', 'subject', 'chapter'])->find($id);

        if (!$video) {
            return response()->json([
                'status' => 'error',
                'message' => 'Video not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Video retrieved successfully',
            'data' => $video,
        ]);
    }

    /**
     * Display all videos belonging to a specific chapter.
     *
     * @param int|string $chapterId
     * @return JsonResponse
     */
    public function getByChapter($chapterId): JsonResponse
    {
        $chapter = Chapter::find($chapterId);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        $videos = Video::where('chapter_id', $chapterId)->with(['grade', 'subject', 'chapter'])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Videos retrieved successfully',
            'data' => $videos,
        ]);
    }

    /**
     * Create a new video with relationship data integrity validation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required|integer|exists:chapters,id',
            'grade_id' => 'sometimes|integer|exists:grades,id',
            'subject_id' => 'sometimes|integer|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|string|url',
            'thumbnail_url' => 'nullable|string|url',
            'duration' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chapter = Chapter::find($request->chapter_id);

        if ($request->has('grade_id') && (int)$request->grade_id !== (int)$chapter->grade_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'grade_id' => ['The selected grade does not match the specified chapter.'],
                ],
            ], 422);
        }

        if ($request->has('subject_id') && (int)$request->subject_id !== (int)$chapter->subject_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'subject_id' => ['The selected subject does not match the specified chapter.'],
                ],
            ], 422);
        }

        $video = Video::create([
            'chapter_id' => $chapter->id,
            'grade_id' => $chapter->grade_id,
            'subject_id' => $chapter->subject_id,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'thumbnail_url' => $request->thumbnail_url,
            'duration' => $request->duration,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Video created successfully',
            'data' => $video,
        ], 201);
    }
}
