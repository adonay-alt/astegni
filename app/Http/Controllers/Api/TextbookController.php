<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Textbook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TextbookController extends Controller
{
    /**
     * Display a listing of textbooks with optional filtering by grade_id, subject_id, or chapter_id.
     * Caches query results for high performance.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $gradeId = $request->query('grade_id');
        $subjectId = $request->query('subject_id');
        $chapterId = $request->query('chapter_id');

        $cacheKey = 'api_textbooks_g' . ($gradeId ?? 'all') . '_s' . ($subjectId ?? 'all') . '_c' . ($chapterId ?? 'all');

        $textbooks = Cache::remember($cacheKey, 3600, function () use ($gradeId, $subjectId, $chapterId) {
            $query = Textbook::select(['id', 'grade_id', 'subject_id', 'chapter_id', 'title', 'description', 'file_url'])
                ->with([
                    'grade:id,name',
                    'subject:id,title',
                    'chapter:id,title,chapter_number'
                ]);

            if ($gradeId !== null) {
                $query->where('grade_id', $gradeId);
            }
            if ($subjectId !== null) {
                $query->where('subject_id', $subjectId);
            }
            if ($chapterId !== null) {
                $query->where('chapter_id', $chapterId);
            }

            return $query->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Textbooks retrieved successfully',
            'data' => $textbooks,
        ]);
    }

    /**
     * Display the specified textbook with lightweight grade, subject, and chapter info.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $textbook = Textbook::select(['id', 'grade_id', 'subject_id', 'chapter_id', 'title', 'description', 'file_url'])
            ->with([
                'grade:id,name',
                'subject:id,title',
                'chapter:id,title,chapter_number'
            ])
            ->find($id);

        if (!$textbook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Textbook not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Textbook retrieved successfully',
            'data' => $textbook,
        ]);
    }

    /**
     * Display all textbooks belonging to a specific chapter.
     *
     * @param int|string $chapterId
     * @return JsonResponse
     */
    public function getByChapter($chapterId): JsonResponse
    {
        $textbooks = Cache::remember('api_textbooks_chapter_' . $chapterId, 3600, function () use ($chapterId) {
            return Textbook::select(['id', 'grade_id', 'subject_id', 'chapter_id', 'title', 'description', 'file_url'])
                ->where('chapter_id', $chapterId)
                ->with([
                    'grade:id,name',
                    'subject:id,title',
                    'chapter:id,title,chapter_number'
                ])
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Textbooks retrieved successfully',
            'data' => $textbooks,
        ]);
    }
}
