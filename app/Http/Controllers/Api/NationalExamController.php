<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NationalExam;
use App\Models\NationalExamQuestion;
use App\Models\NationalExamSubject;
use App\Models\NationalExamYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NationalExamController extends Controller
{
    // ==========================================
    // NATIONAL EXAMS
    // ==========================================

    public function index(Request $request): JsonResponse
    {
        try {
            $query = NationalExam::with(['grade']);

            if ($request->has('grade_id') && $request->grade_id !== null) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->has('year') && $request->year !== null) {
                $query->whereHas('years', function ($yearQuery) use ($request) {
                    $yearQuery->where('year', $request->year);
                });
            }
            if ($request->has('subject_id') && $request->subject_id !== null) {
                $query->whereHas('years.subjects', function ($subjectQuery) use ($request) {
                    $subjectQuery->where('id', $request->subject_id);
                });
            }

            $exams = $query->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'National exams retrieved successfully',
                'data'    => $exams,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NationalExamController@index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load national exams: ' . $e->getMessage(),
                'data'    => [],
            ], 200);
        }
    }

    public function show($id): JsonResponse
    {
        $exam = NationalExam::with(['grade', 'years.subjects.stream'])->find($id);

        if (!$exam) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'National exam retrieved successfully',
            'data' => $exam,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => 'required|integer|exists:grades,id',
            'title'    => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $exam = NationalExam::create([
            'grade_id' => $request->input('grade_id'),
            'title'    => trim($request->input('title')),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam created successfully',
            'data'    => $exam->load('grade'),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $exam = NationalExam::find($id);

        if (!$exam) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'grade_id' => 'sometimes|required|integer|exists:grades,id',
            'title'    => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [];
        if ($request->has('grade_id')) {
            $data['grade_id'] = $request->input('grade_id');
        }
        if ($request->has('title')) {
            $data['title'] = trim($request->input('title'));
        }

        $exam->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam updated successfully',
            'data'    => $exam->fresh('grade'),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $exam = NationalExam::find($id);

        if (!$exam) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam not found',
            ], 404);
        }

        $exam->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam deleted successfully',
        ]);
    }

    public function getYearsByExam($national_exam_id): JsonResponse
    {
        try {
            $exam = NationalExam::find($national_exam_id);

            if (!$exam) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'National exam not found',
                    'data'    => [],
                ], 404);
            }

            $years = NationalExamYear::where('national_exam_id', $national_exam_id)
                ->with(['grade'])
                ->orderBy('year', 'desc')
                ->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'National exam years retrieved successfully',
                'data'    => $years,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NationalExamController@getYearsByExam error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load exam years: ' . $e->getMessage(),
                'data'    => [],
            ], 200);
        }
    }

    // ==========================================
    // NATIONAL EXAM YEARS
    // ==========================================

    /**
     * List all national exam years, optionally filtered by exam or grade.
     * GET /api/national-exam-years
     */
    public function indexYears(Request $request): JsonResponse
    {
        try {
            $query = NationalExamYear::with(['grade', 'nationalExam']);

            if ($request->filled('national_exam_id')) {
                $query->where('national_exam_id', $request->national_exam_id);
            }
            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            $years = $query->orderBy('id', 'desc')->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'National exam years retrieved successfully',
                'data'    => $years,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NationalExamController@indexYears error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load exam years: ' . $e->getMessage(),
                'data'    => [],
            ], 200);
        }
    }

    public function showYear($id): JsonResponse
    {
        try {
            $year = NationalExamYear::with(['grade', 'nationalExam', 'subjects.stream'])->find($id);

            if (!$year) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'National exam year not found',
                    'data'    => null,
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'National exam year retrieved successfully',
                'data'    => $year,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NationalExamController@showYear error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load exam year: ' . $e->getMessage(),
                'data'    => null,
            ], 200);
        }
    }

    public function getSubjectsByYear(Request $request, $year_id): JsonResponse
    {
        try {
            $year = NationalExamYear::find($year_id);

            if (!$year) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'National exam year not found',
                    'data'    => [],
                ], 404);
            }

            $query = NationalExamSubject::where('national_exam_year_id', $year_id)
                ->with(['grade', 'stream', 'nationalExam', 'nationalExamYear'])
                ->withCount('questions');

            // Apply Stream filtering rule:
            // stream_id == null: Common Core (accessible to all)
            // stream_id == 1: Natural Science
            // stream_id == 2: Social Science
            if ($request->filled('stream_id')) {
                $streamId = (int) $request->input('stream_id');
                $query->forStream($streamId);
            } elseif ($request->filled('stream')) {
                $streamSlug = strtolower(trim((string) $request->input('stream')));
                if ($streamSlug === 'natural') {
                    $query->forStream(1);
                } elseif ($streamSlug === 'social') {
                    $query->forStream(2);
                } elseif ($streamSlug === 'common') {
                    $query->whereNull('stream_id');
                }
            }

            $subjects = $query->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'National exam subjects retrieved successfully',
                'data'    => $subjects,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NationalExamController@getSubjectsByYear error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load exam subjects: ' . $e->getMessage(),
                'data'    => [],
            ], 200);
        }
    }

    // ==========================================
    // NATIONAL EXAM SUBJECTS
    // ==========================================

    public function indexSubjects(Request $request): JsonResponse
    {
        try {
            $query = NationalExamSubject::with(['grade', 'stream', 'nationalExam', 'nationalExamYear']);

            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->filled('national_exam_id')) {
                $query->where('national_exam_id', $request->national_exam_id);
            }
            if ($request->filled('national_exam_year_id')) {
                $query->where('national_exam_year_id', $request->national_exam_year_id);
            }

            if ($request->filled('stream_id')) {
                $streamId = (int) $request->input('stream_id');
                $query->forStream($streamId);
            } elseif ($request->filled('stream')) {
                $streamSlug = strtolower(trim((string) $request->input('stream')));
                if ($streamSlug === 'natural') {
                    $query->forStream(1);
                } elseif ($streamSlug === 'social') {
                    $query->forStream(2);
                } elseif ($streamSlug === 'common') {
                    $query->whereNull('stream_id');
                }
            }

            $subjects = $query->get();

            return response()->json([
                'status'  => 'success',
                'message' => 'National exam subjects retrieved successfully',
                'data'    => $subjects,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NationalExamController@indexSubjects error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load exam subjects: ' . $e->getMessage(),
                'data'    => [],
            ], 200);
        }
    }

    public function showSubject($id): JsonResponse
    {
        $subject = NationalExamSubject::with(['grade', 'stream', 'nationalExam', 'nationalExamYear', 'questions'])->find($id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam subject not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'National exam subject retrieved successfully',
            'data' => $subject,
        ]);
    }

    public function getQuestionsBySubject($subject_id): JsonResponse
    {
        $subject = NationalExamSubject::find($subject_id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam subject not found',
            ], 404);
        }

        $questions = NationalExamQuestion::where('national_exam_subject_id', $subject_id)
            ->with(['grade', 'nationalExam', 'nationalExamYear', 'nationalExamSubject.stream'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'National exam questions retrieved successfully',
            'data' => $questions,
        ]);
    }

    // ==========================================
    // NATIONAL EXAM QUESTIONS
    // ==========================================

    public function indexQuestions(Request $request): JsonResponse
    {
        $query = NationalExamQuestion::with(['grade', 'nationalExam', 'nationalExamYear', 'nationalExamSubject.stream']);

        if ($request->has('grade_id') && $request->grade_id !== null) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->has('national_exam_id') && $request->national_exam_id !== null) {
            $query->where('national_exam_id', $request->national_exam_id);
        }
        if ($request->has('national_exam_year_id') && $request->national_exam_year_id !== null) {
            $query->where('national_exam_year_id', $request->national_exam_year_id);
        }
        if ($request->has('national_exam_subject_id') && $request->national_exam_subject_id !== null) {
            $query->where('national_exam_subject_id', $request->national_exam_subject_id);
        }

        $questions = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'National exam questions retrieved successfully',
            'data' => $questions,
        ]);
    }

    public function showQuestion($id): JsonResponse
    {
        $question = NationalExamQuestion::with(['grade', 'nationalExam', 'nationalExamYear', 'nationalExamSubject.stream'])->find($id);

        if (!$question) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam question not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'National exam question retrieved successfully',
            'data' => $question,
        ]);
    }

    /**
     * Submit answers for a national exam subject and calculate best score.
     *
     * @param Request $request
     * @param int|string $national_exam_id
     * @return JsonResponse
     */
    public function submit(Request $request, $national_exam_id): JsonResponse
    {
        $userId = $request->user()?->id ?? auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $exam = NationalExam::find($national_exam_id);

        if (!$exam) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'national_exam_year_id' => 'required|integer|exists:national_exam_years,id',
            'national_exam_subject_id' => 'required|integer|exists:national_exam_subjects,id',
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

        $examYear = NationalExamYear::find($request->national_exam_year_id);
        if ((int)$examYear->national_exam_id !== (int)$exam->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'national_exam_year_id' => ['The selected exam year does not match the national exam.'],
                ],
            ], 422);
        }

        $subject = NationalExamSubject::find($request->national_exam_subject_id);
        if ((int)$subject->national_exam_year_id !== (int)$examYear->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'national_exam_subject_id' => ['The selected subject does not match the exam year.'],
                ],
            ], 422);
        }
        if ((int)$subject->national_exam_id !== (int)$exam->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'national_exam_subject_id' => ['The selected subject does not match the national exam.'],
                ],
            ], 422);
        }

        if ((int)$subject->grade_id !== (int)$exam->grade_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'grade_id' => ['Grade mismatch across national exam hierarchy.'],
                ],
            ], 422);
        }

        $questions = NationalExamQuestion::where('national_exam_subject_id', $subject->id)->get();
        $validQuestionIds = $questions->pluck('id')->map(fn($id) => (string)$id)->all();

        $answers = $request->input('answers', []);

        foreach (array_keys($answers) as $submittedQId) {
            if (!in_array((string)$submittedQId, $validQuestionIds, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => [
                        'answers' => ["Question ID {$submittedQId} does not belong to this national exam subject."],
                    ],
                ], 422);
            }
        }

        $totalQuestions = $questions->count();
        $answeredQuestions = 0;
        $correctAnswers = 0;
        $incorrectAnswers = 0;
        $results = [];

        foreach ($questions as $question) {
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

        $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

        $existingResult = \App\Models\NationalExamResult::where('national_exam_id', $exam->id)
            ->where('national_exam_year_id', $examYear->id)
            ->where('national_exam_subject_id', $subject->id)
            ->where('user_id', $userId)
            ->first();

        $isNewBest = false;

        if (!$existingResult) {
            $resultRecord = \App\Models\NationalExamResult::create([
                'national_exam_id' => $exam->id,
                'national_exam_year_id' => $examYear->id,
                'national_exam_subject_id' => $subject->id,
                'grade_id' => $subject->grade_id,
                'stream_id' => $subject->stream_id,
                'user_id' => $userId,
                'score' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'completed_at' => now(),
            ]);
            $isNewBest = true;
        } else {
            $isHigher = ($percentage > (float)$existingResult->percentage) ||
                (abs($percentage - (float)$existingResult->percentage) < 0.001 && $correctAnswers > $existingResult->score);

            if ($isHigher) {
                $existingResult->update([
                    'score' => $correctAnswers,
                    'total_questions' => $totalQuestions,
                    'percentage' => $percentage,
                    'completed_at' => now(),
                ]);
                $resultRecord = $existingResult;
                $isNewBest = true;
            } else {
                $resultRecord = $existingResult;
                $isNewBest = false;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'National exam submitted successfully',
            'data' => [
                'national_exam_id' => $exam->id,
                'national_exam_year_id' => $examYear->id,
                'national_exam_subject_id' => $subject->id,
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'score' => $percentage,
                'attempt_score' => $correctAnswers,
                'attempt_total_questions' => $totalQuestions,
                'attempt_percentage' => $percentage,
                'best_score' => $resultRecord->score,
                'best_total_questions' => $resultRecord->total_questions,
                'best_percentage' => $resultRecord->percentage,
                'is_new_best' => $isNewBest,
                'results' => $results,
            ],
            'result' => [
                'national_exam_id' => $resultRecord->national_exam_id,
                'national_exam_year_id' => $resultRecord->national_exam_year_id,
                'national_exam_subject_id' => $resultRecord->national_exam_subject_id,
                'score' => $resultRecord->score,
                'total_questions' => $resultRecord->total_questions,
                'percentage' => $resultRecord->percentage,
                'completed_at' => $resultRecord->completed_at ? $resultRecord->completed_at->toISOString() : null,
                'is_new_best' => $isNewBest,
            ],
        ]);
    }

    /**
     * Get the authenticated student's best result for a national exam.
     *
     * @param Request $request
     * @param int|string $national_exam_id
     * @return JsonResponse
     */
    public function result(Request $request, $national_exam_id): JsonResponse
    {
        $userId = $request->user()?->id ?? auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $query = \App\Models\NationalExamResult::where('national_exam_id', $national_exam_id)
            ->where('user_id', $userId)
            ->with(['nationalExam', 'nationalExamYear', 'nationalExamSubject', 'grade', 'stream']);

        if ($request->has('year_id') && $request->year_id !== null) {
            $query->where('national_exam_year_id', $request->year_id);
        }
        if ($request->has('subject_id') && $request->subject_id !== null) {
            $query->where('national_exam_subject_id', $request->subject_id);
        }
        if ($request->has('stream_id') && $request->stream_id !== null) {
            $query->where('stream_id', $request->stream_id);
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam result not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'National exam result retrieved successfully',
            'data' => $request->has('subject_id') ? $results->first() : $results,
        ]);
    }

    /**
     * Get the authenticated student's best result for a specific national exam subject.
     *
     * @param Request $request
     * @param int|string $subject_id
     * @return JsonResponse
     */
    public function subjectResult(Request $request, $subject_id): JsonResponse
    {
        $userId = $request->user()?->id ?? auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $result = \App\Models\NationalExamResult::where('national_exam_subject_id', $subject_id)
            ->where('user_id', $userId)
            ->with(['nationalExam', 'nationalExamYear', 'nationalExamSubject', 'grade', 'stream'])
            ->first();

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'National exam result not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'National exam result retrieved successfully',
            'data' => $result,
        ]);
    }
}
