<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NationalExamSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NationalExamSubjectController extends Controller
{
    /**
     * Display a listing of national exam subjects.
     * GET /api/national-exam-subjects
     */
    public function index(Request $request): JsonResponse
    {
        $query = NationalExamSubject::with(['nationalExam', 'nationalExamYear', 'grade', 'stream'])
            ->withCount('questions');

        if ($request->filled('national_exam_id')) {
            $query->where('national_exam_id', $request->input('national_exam_id'));
        }
        if ($request->filled('national_exam_year_id')) {
            $query->where('national_exam_year_id', $request->input('national_exam_year_id'));
        }
        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->input('grade_id'));
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->input('stream_id'));
        }

        $subjects = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam subjects retrieved successfully',
            'data'    => $subjects,
        ]);
    }

    /**
     * Display the specified national exam subject.
     * GET /api/national-exam-subjects/{id}
     */
    public function show($id): JsonResponse
    {
        $subject = NationalExamSubject::with(['nationalExam', 'nationalExamYear', 'grade', 'stream', 'questions'])
            ->find($id);

        if (!$subject) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam subject not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam subject retrieved successfully',
            'data'    => $subject,
        ]);
    }

    /**
     * Store a newly created national exam subject in storage.
     * POST /api/national-exam-subjects
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'national_exam_id'      => 'required|integer|exists:national_exams,id',
            'national_exam_year_id' => 'required|integer|exists:national_exam_years,id',
            'grade_id'              => 'required|integer|exists:grades,id',
            'stream_id'             => 'nullable|integer|exists:streams,id',
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $subject = NationalExamSubject::create([
            'national_exam_id'      => $request->input('national_exam_id'),
            'national_exam_year_id' => $request->input('national_exam_year_id'),
            'grade_id'              => $request->input('grade_id'),
            'stream_id'             => $request->input('stream_id'),
            'name'                  => trim($request->input('name')),
            'description'           => $request->input('description'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam subject created successfully',
            'data'    => $subject->load(['nationalExam', 'nationalExamYear', 'grade', 'stream']),
        ], 201);
    }

    /**
     * Update the specified national exam subject in storage.
     * PUT/PATCH /api/national-exam-subjects/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $subject = NationalExamSubject::find($id);

        if (!$subject) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam subject not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'national_exam_id'      => 'sometimes|required|integer|exists:national_exams,id',
            'national_exam_year_id' => 'sometimes|required|integer|exists:national_exam_years,id',
            'grade_id'              => 'sometimes|required|integer|exists:grades,id',
            'stream_id'             => 'nullable|integer|exists:streams,id',
            'name'                  => 'sometimes|required|string|max:255',
            'description'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [];
        if ($request->has('national_exam_id')) {
            $data['national_exam_id'] = $request->input('national_exam_id');
        }
        if ($request->has('national_exam_year_id')) {
            $data['national_exam_year_id'] = $request->input('national_exam_year_id');
        }
        if ($request->has('grade_id')) {
            $data['grade_id'] = $request->input('grade_id');
        }
        if ($request->has('stream_id')) {
            $data['stream_id'] = $request->input('stream_id');
        }
        if ($request->has('name')) {
            $data['name'] = trim($request->input('name'));
        }
        if ($request->has('description')) {
            $data['description'] = $request->input('description');
        }

        $subject->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam subject updated successfully',
            'data'    => $subject->fresh(['nationalExam', 'nationalExamYear', 'grade', 'stream']),
        ]);
    }

    /**
     * Remove the specified national exam subject from storage.
     * DELETE /api/national-exam-subjects/{id}
     */
    public function destroy($id): JsonResponse
    {
        $subject = NationalExamSubject::find($id);

        if (!$subject) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam subject not found',
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam subject deleted successfully',
        ]);
    }
}
