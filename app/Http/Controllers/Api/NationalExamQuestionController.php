<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NationalExamQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NationalExamQuestionController extends Controller
{
    /**
     * Display a listing of national exam questions.
     * GET /api/national-exam-questions
     */
    public function index(Request $request): JsonResponse
    {
        $query = NationalExamQuestion::with(['nationalExam', 'nationalExamYear', 'nationalExamSubject.stream', 'grade']);

        if ($request->filled('national_exam_id')) {
            $query->where('national_exam_id', $request->input('national_exam_id'));
        }
        if ($request->filled('national_exam_year_id')) {
            $query->where('national_exam_year_id', $request->input('national_exam_year_id'));
        }
        if ($request->filled('national_exam_subject_id')) {
            $query->where('national_exam_subject_id', $request->input('national_exam_subject_id'));
        }
        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->input('grade_id'));
        }

        $perPage = $request->input('per_page');
        if ($perPage) {
            $questions = $query->orderBy('question_number', 'asc')->orderBy('id', 'asc')->paginate((int) $perPage);
        } else {
            $questions = $query->orderBy('question_number', 'asc')->orderBy('id', 'asc')->get();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam questions retrieved successfully',
            'data'    => $questions,
        ]);
    }

    /**
     * Display the specified national exam question.
     * GET /api/national-exam-questions/{id}
     */
    public function show($id): JsonResponse
    {
        $question = NationalExamQuestion::with(['nationalExam', 'nationalExamYear', 'nationalExamSubject.stream', 'grade'])
            ->find($id);

        if (!$question) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam question not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam question retrieved successfully',
            'data'    => $question,
        ]);
    }

    /**
     * Store a newly created national exam question in storage.
     * POST /api/national-exam-questions
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'national_exam_id'         => 'required|integer|exists:national_exams,id',
            'national_exam_year_id'    => 'required|integer|exists:national_exam_years,id',
            'national_exam_subject_id' => 'required|integer|exists:national_exam_subjects,id',
            'grade_id'                 => 'required|integer|exists:grades,id',
            'question_number'          => 'nullable|integer',
            'question'                 => 'required|string',
            'image_url'                => 'nullable|string|max:2048',
            'option_a'                 => 'required|string',
            'option_b'                 => 'required|string',
            'option_c'                 => 'required|string',
            'option_d'                 => 'required|string',
            'correct_answer'           => 'required|string|in:option_a,option_b,option_c,option_d',
            'explanation'              => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $question = NationalExamQuestion::create([
            'national_exam_id'         => $request->input('national_exam_id'),
            'national_exam_year_id'    => $request->input('national_exam_year_id'),
            'national_exam_subject_id' => $request->input('national_exam_subject_id'),
            'grade_id'                 => $request->input('grade_id'),
            'question_number'          => $request->input('question_number'),
            'question'                 => $request->input('question'),
            'image_url'                => $request->input('image_url'),
            'option_a'                 => $request->input('option_a'),
            'option_b'                 => $request->input('option_b'),
            'option_c'                 => $request->input('option_c'),
            'option_d'                 => $request->input('option_d'),
            'correct_answer'           => $request->input('correct_answer'),
            'explanation'              => $request->input('explanation'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam question created successfully',
            'data'    => $question->load(['nationalExam', 'nationalExamYear', 'nationalExamSubject.stream', 'grade']),
        ], 201);
    }

    /**
     * Update the specified national exam question in storage.
     * PUT/PATCH /api/national-exam-questions/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $question = NationalExamQuestion::find($id);

        if (!$question) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam question not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'national_exam_id'         => 'sometimes|required|integer|exists:national_exams,id',
            'national_exam_year_id'    => 'sometimes|required|integer|exists:national_exam_years,id',
            'national_exam_subject_id' => 'sometimes|required|integer|exists:national_exam_subjects,id',
            'grade_id'                 => 'sometimes|required|integer|exists:grades,id',
            'question_number'          => 'nullable|integer',
            'question'                 => 'sometimes|required|string',
            'image_url'                => 'nullable|string|max:2048',
            'option_a'                 => 'sometimes|required|string',
            'option_b'                 => 'sometimes|required|string',
            'option_c'                 => 'sometimes|required|string',
            'option_d'                 => 'sometimes|required|string',
            'correct_answer'           => 'sometimes|required|string|in:option_a,option_b,option_c,option_d',
            'explanation'              => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $fields = [
            'national_exam_id',
            'national_exam_year_id',
            'national_exam_subject_id',
            'grade_id',
            'question_number',
            'question',
            'image_url',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'explanation',
        ];

        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        $question->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam question updated successfully',
            'data'    => $question->fresh(['nationalExam', 'nationalExamYear', 'nationalExamSubject.stream', 'grade']),
        ]);
    }

    /**
     * Remove the specified national exam question from storage.
     * DELETE /api/national-exam-questions/{id}
     */
    public function destroy($id): JsonResponse
    {
        $question = NationalExamQuestion::find($id);

        if (!$question) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam question not found',
            ], 404);
        }

        $question->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam question deleted successfully',
        ]);
    }
}
