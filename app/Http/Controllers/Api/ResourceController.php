<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * Display a listing of resources with optional filtering by grade_id, subject_id, or chapter_id.
     *
     * @param Request $request
     * @return JsonResponse
     */
     
    public function index(Request $request): JsonResponse
    {
        $query = Resource::with(['grade', 'subject', 'chapter']);

        if ($request->has('grade_id') && $request->grade_id !== null) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->has('subject_id') && $request->subject_id !== null) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('chapter_id') && $request->chapter_id !== null) {
            $query->where('chapter_id', $request->chapter_id);
        }

        $resources = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Resources retrieved successfully',
            'data' => $resources,
        ]);
    }

    /**
     * Display the specified resource with its chapter.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $resource = Resource::with(['grade', 'subject', 'chapter'])->find($id);

        if (!$resource) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Resource retrieved successfully',
            'data' => $resource,
        ]);
    }

    /**
     * Display all resources belonging to a specific chapter.
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

        $resources = Resource::where('chapter_id', $chapterId)->with(['grade', 'subject', 'chapter'])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Resources retrieved successfully',
            'data' => $resources,
        ]);
    }
}
