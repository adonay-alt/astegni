<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class GradesController extends Controller
{
    /**
     * Return a list of all grades with their associated subjects.
     * Cached for fast subsequent retrieval.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $grades = Cache::remember('api_grades_list', 3600, function () {
            return Grade::select('id', 'name', 'created_at')
                ->with(['subjects' => function ($q) {
                    $q->select('id', 'grade_id', 'title', 'stream_id');
                }])
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Grades retrieved successfully',
            'data' => $grades,
        ]);
    }
}
