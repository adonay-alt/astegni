<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes with optional filtering by grade_id, subject_id, or chapter_id.
     *
     * @param Request $request
     * @return JsonResponse
     */
     
    public function index(Request $request): JsonResponse
    {
        $query = Quiz::with(['grade', 'subject', 'chapter', 'questions']);

        if ($request->has('grade_id') && $request->grade_id !== null) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->has('subject_id') && $request->subject_id !== null) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('chapter_id') && $request->chapter_id !== null) {
            $query->where('chapter_id', $request->chapter_id);
        }

        $quizzes = $query->get();

        foreach ($quizzes as $quiz) {
            $quiz->questions->makeHidden(['correct_answer', 'explanation']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Quizzes retrieved successfully',
            'data' => $quizzes,
        ]);
    }

    /**
     * Display the specified quiz.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $quiz = Quiz::with(['grade', 'subject', 'chapter', 'questions'])->find($id);

        if (!$quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found',
            ], 404);
        }

        $quiz->questions->makeHidden(['correct_answer', 'explanation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Quiz retrieved successfully',
            'data' => $quiz,
        ]);
    }

    /**
     * Display questions for a specific quiz.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function getQuestions($id): JsonResponse
    {
        $quiz = Quiz::with('questions')->find($id);

        if (!$quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found',
            ], 404);
        }

        $questions = $quiz->questions;
        $questions->makeHidden(['correct_answer', 'explanation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Quiz questions retrieved successfully',
            'data' => $questions,
        ]);
    }

    /**
     * Display a listing of quiz questions with optional quiz_id filtering.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function indexQuestions(Request $request): JsonResponse
    {
        $query = QuizQuestion::query();

        if ($request->has('quiz_id') && $request->quiz_id !== null) {
            $query->where('quiz_id', $request->quiz_id);
        }

        $questions = $query->get();
        $questions->makeHidden(['correct_answer', 'explanation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Quiz questions retrieved successfully',
            'data' => $questions,
        ]);
    }

    /**
     * Display all quizzes belonging to a specific chapter.
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

        $quizzes = Quiz::where('chapter_id', $chapterId)->with(['grade', 'subject', 'chapter', 'questions'])->get();

        foreach ($quizzes as $quiz) {
            $quiz->questions->makeHidden(['correct_answer', 'explanation']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Quizzes retrieved successfully',
            'data' => $quizzes,
        ]);
    }

    /**
     * Submit answers for a quiz and calculate results.
     *
     * @param Request $request
     * @param int|string $quiz_id
     * @return JsonResponse
     */
    public function submit(Request $request, $quiz_id): JsonResponse
    {
        $quiz = Quiz::with('questions')->find($quiz_id);

        if (!$quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'present|array',
            'answers.*' => 'required|string|in:option_a,option_b,option_c,option_d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answers = $request->input('answers', []);
        $quizQuestions = $quiz->questions;
        $validQuestionIds = $quizQuestions->pluck('id')->map(fn($id) => (string)$id)->all();

        foreach (array_keys($answers) as $submittedQId) {
            if (!in_array((string)$submittedQId, $validQuestionIds, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => [
                        'answers' => ["Question ID {$submittedQId} does not belong to this quiz."],
                    ],
                ], 422);
            }
        }

        $totalQuestions = $quizQuestions->count();
        $answeredQuestions = 0;
        $correctAnswers = 0;
        $incorrectAnswers = 0;
        $results = [];

        foreach ($quizQuestions as $question) {
            $qId = $question->id;
            $qIdStr = (string)$qId;

            if (array_key_exists($qId, $answers) || array_key_exists($qIdStr, $answers)) {
                $selectedAnswer = array_key_exists($qId, $answers) ? $answers[$qId] : $answers[$qIdStr];
                $isCorrect = ($selectedAnswer === $question->correct_answer);

                $answeredQuestions++;
                if ($isCorrect) {
                    $correctAnswers++;
                } else {
                    $incorrectAnswers++;
                }

                $results[] = [
                    'question_id' => $question->id,
                    'selected_answer' => $selectedAnswer,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                ];
            } else {
                $results[] = [
                    'question_id' => $question->id,
                    'selected_answer' => null,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => false,
                ];
            }
        }

        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        $userId = $request->user()?->id ?? auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        $existingResult = QuizResult::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();

        $isNewBest = false;

        if (!$existingResult) {
            $quizResult = QuizResult::create([
                'quiz_id' => $quiz->id,
                'user_id' => $userId,
                'score' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'completed_at' => now(),
            ]);
            $isNewBest = true;
        } else {
            // Compare new attempt with stored best result
            // Higher percentage (or higher score if total questions changed) replaces stored result
            $isHigher = ($percentage > (float) $existingResult->percentage) ||
                (abs($percentage - (float) $existingResult->percentage) < 0.001 && $correctAnswers > $existingResult->score);

            if ($isHigher) {
                $existingResult->update([
                    'score' => $correctAnswers,
                    'total_questions' => $totalQuestions,
                    'percentage' => $percentage,
                    'completed_at' => now(),
                ]);
                $quizResult = $existingResult;
                $isNewBest = true;
            } else {
                // Lower or equal score does NOT update stored result
                $quizResult = $existingResult;
                $isNewBest = false;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Quiz submitted successfully',
            'data' => [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'score' => $score,
                'attempt_score' => $correctAnswers,
                'attempt_total_questions' => $totalQuestions,
                'attempt_percentage' => $percentage,
                'best_score' => $quizResult->score,
                'best_total_questions' => $quizResult->total_questions,
                'best_percentage' => $quizResult->percentage,
                'is_new_best' => $isNewBest,
                'results' => $results,
            ],
            'result' => [
                'quiz_id' => $quizResult->quiz_id,
                'score' => $quizResult->score,
                'total_questions' => $quizResult->total_questions,
                'percentage' => $quizResult->percentage,
                'completed_at' => $quizResult->completed_at ? $quizResult->completed_at->toISOString() : null,
                'is_new_best' => $isNewBest,
            ],
        ]);
    }

    /**
     * Get the authenticated student's result for a specific quiz.
     *
     * @param Request $request
     * @param int|string $quiz_id
     * @return JsonResponse
     */
    public function result(Request $request, $quiz_id): JsonResponse
    {
        $userId = $request->user()?->id ?? auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $result = QuizResult::where('quiz_id', $quiz_id)
            ->where('user_id', $userId)
            ->first();

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz result not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'quiz_id' => $result->quiz_id,
                'score' => $result->score,
                'total_questions' => $result->total_questions,
                'percentage' => $result->percentage,
                'completed_at' => $result->completed_at ? $result->completed_at->toISOString() : null,
            ],
        ]);
    }
}
