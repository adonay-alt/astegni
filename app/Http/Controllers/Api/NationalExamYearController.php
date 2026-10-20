<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NationalExamYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NationalExamYearController extends Controller
{
    /**
     * Display a listing of national exam years.
     * GET /api/national-exam-years
     */
    public function index(Request $request): JsonResponse
    {
        $query = NationalExamYear::with(['nationalExam', 'grade']);

        if ($request->filled('national_exam_id')) {
            $query->where('national_exam_id', $request->input('national_exam_id'));
        }
        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->input('grade_id'));
        }
        if ($request->filled('year')) {
            $query->where('year', $request->input('year'));
        }

        $years = $query->orderBy('year', 'desc')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam years retrieved successfully',
            'data'    => $years,
        ]);
    }

    /**
     * Display the specified national exam year.
     * GET /api/national-exam-years/{id}
     */
    public function show($id): JsonResponse
    {
        $year = NationalExamYear::with(['nationalExam', 'grade', 'subjects.stream'])->find($id);

        if (!$year) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam year not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam year retrieved successfully',
            'data'    => $year,
        ]);
    }

    /**
     * Store a newly created national exam year in storage.
     * POST /api/national-exam-years
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'national_exam_id' => 'required|integer|exists:national_exams,id',
            'grade_id'         => 'required|integer|exists:grades,id',
            'year'             => 'required|integer|min:1990|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $year = NationalExamYear::create([
            'national_exam_id' => $request->input('national_exam_id'),
            'grade_id'         => $request->input('grade_id'),
            'year'             => (int) $request->input('year'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam year created successfully',
            'data'    => $year->load(['nationalExam', 'grade']),
        ], 201);
    }

    /**
     * Update the specified national exam year in storage.
     * PUT/PATCH /api/national-exam-years/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $year = NationalExamYear::find($id);

        if (!$year) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam year not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'national_exam_id' => 'required|integer|exists:national_exams,id',
            'grade_id'         => 'required|integer|exists:grades,id',
            'year'             => 'required|integer|min:1990|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $year->update([
            'national_exam_id' => $request->input('national_exam_id'),
            'grade_id'         => $request->input('grade_id'),
            'year'             => (int) $request->input('year'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam year updated successfully',
            'data'    => $year->fresh(['nationalExam', 'grade']),
        ]);
    }

    /**
     * Remove the specified national exam year from storage.
     * DELETE /api/national-exam-years/{id}
     */
    public function destroy($id): JsonResponse
    {
        $year = NationalExamYear::find($id);

        if (!$year) {
            return response()->json([
                'status'  => 'error',
                'message' => 'National exam year not found',
            ], 404);
        }

        $year->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'National exam year deleted successfully',
        ]);
    }
}
