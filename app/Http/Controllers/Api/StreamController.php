<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Stream;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StreamController extends Controller
{
    /**
     * Display a listing of streams.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $streams = Stream::all();

        return response()->json([
            'status' => 'success',
            'message' => 'Streams retrieved successfully',
            'data' => $streams,
        ]);
    }

    /**
     * Display the specified stream.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $stream = Stream::with(['subjects'])->find($id);

        if (!$stream) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stream not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Stream retrieved successfully',
            'data' => $stream,
        ]);
    }

    /**
     * Get streams by grade ID.
     *
     * @param int $grade_id
     * @return JsonResponse
     */
    public function getByGrade(int $grade_id): JsonResponse
    {
        $grade = Grade::find($grade_id);

        if (!$grade) {
            return response()->json([
                'status' => 'error',
                'message' => 'Grade not found',
            ], 404);
        }

        // Grades 9 and 10 do not have streams
        if (str_contains(strtolower($grade->name), '9') || str_contains(strtolower($grade->name), '10')) {
            $streams = collect([]);
        } else {
            $streams = Stream::all();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Streams retrieved successfully',
            'data' => $streams,
        ]);
    }

    /**
     * Store a newly created stream (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check uniqueness constraint
        $existing = Stream::where('name', $request->name)->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'name' => ['A stream with this name already exists.'],
                ],
            ], 422);
        }

        $stream = Stream::create([
            'name' => $request->name,
        ]);

        // Record audit trail
        AuditLogService::log('stream.created', $stream, null, [
            'id' => $stream->id,
            'name' => $stream->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stream created successfully',
            'data' => $stream,
        ], 201);
    }

    /**
     * Update the specified stream (Admin only).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $stream = Stream::find($id);

        if (!$stream) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stream not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetName = $request->input('name', $stream->name);

        // Uniqueness check excluding current stream
        $existing = Stream::where('name', $targetName)
            ->where('id', '!=', $stream->id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'name' => ['A stream with this name already exists.'],
                ],
            ], 422);
        }

        $oldName = $stream->name;
        $stream->update([
            'name' => $targetName,
        ]);

        // Record audit trail
        AuditLogService::log('stream.updated', $stream, ['name' => $oldName], ['name' => $stream->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stream updated successfully',
            'data' => $stream->fresh(),
        ]);
    }

    /**
     * Delete the specified stream (Admin only).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        $stream = Stream::find($id);

        if (!$stream) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stream not found',
            ], 404);
        }

        $deletedSnapshot = [
            'id' => $stream->id,
            'name' => $stream->name,
        ];

        $stream->delete();

        // Record audit trail
        AuditLogService::log('stream.deleted', 'App\Models\Stream', $deletedSnapshot, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Stream deleted successfully',
        ]);
    }
}
