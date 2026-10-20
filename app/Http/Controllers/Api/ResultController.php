<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    /**
     * Display the authenticated student's combined quiz and exam results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myResults(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Fetch Quiz Results
        $quizResults = $user->quizResults()
            ->with(['quiz.subject', 'quiz.chapter'])
            ->latest()
            ->get()
            ->map(function ($res) {
                $completedDate = $res->completed_at ?? $res->created_at;
                return [
                    'type' => 'quiz',
                    'id' => $res->id,
                    'subject' => $res->quiz?->subject?->title ?? 'N/A',
                    'chapter' => $res->quiz?->chapter?->title ?? 'N/A',
                    'score' => (int) $res->score,
                    'total' => (int) $res->total_questions,
                    'percentage' => (float) $res->percentage,
                    'date' => $completedDate ? $completedDate->toIso8601String() : null,
                ];
            });

        // 2. Fetch National Exam Results
        $examResults = $user->nationalExamResults()
            ->with(['nationalExamSubject', 'grade'])
            ->latest()
            ->get()
            ->map(function ($res) {
                $completedDate = $res->completed_at ?? $res->created_at;
                return [
                    'type' => 'exam',
                    'id' => $res->id,
                    'subject' => $res->nationalExamSubject?->name ?? $res->nationalExamSubject?->title ?? 'National Exam',
                    'chapter' => null,
                    'score' => (int) $res->score,
                    'total' => (int) $res->total_questions,
                    'percentage' => (float) $res->percentage,
                    'date' => $completedDate ? $completedDate->toIso8601String() : null,
                ];
            });

        // 3. Combine results and sort by date descending
        $combinedResults = $quizResults->concat($examResults)->sortByDesc('date')->values()->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'results' => $combinedResults,
            ],
        ], 200);
    }
}
