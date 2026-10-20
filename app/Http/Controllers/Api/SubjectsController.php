<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CurriculumCacheHelper;
use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Subject;
use App\Services\AuditLogService;
use App\Services\CurriculumCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SubjectsController extends Controller
{
    /**
     * Return a list of subjects filtered by grade_id and optional stream_id (Public).
     * Caches static listings for high performance.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'stream_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gradeId = $request->filled('grade_id') ? (int) $request->query('grade_id') : null;
        $streamId = $request->query('stream_id');

        $cacheKey = 'api_subjects_g' . ($gradeId ?? 'all') . '_s' . ($streamId ?? 'all');

        $subjects = Cache::remember($cacheKey, 3600, function () use ($gradeId, $streamId) {
            $query = Subject::with('chapters');

            if ($gradeId !== null) {
                $query->where('grade_id', $gradeId);
            }

            if ($streamId !== null && $streamId !== '') {
                $streamIdInt = (int) $streamId;
                $query->where(function ($q) use ($streamIdInt) {
                    $q->where('stream_id', $streamIdInt)
                      ->orWhereNull('stream_id');
                });
            }

            return $query->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Subjects retrieved successfully',
            'data' => $subjects,
        ]);
    }

    /**
     * Return a comprehensive list of subjects for Admin with filtering.
     * Always returns live uncached data with relation counts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'stream_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'with_chapters' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Subject::select([
            'id',
            'title',
            'description',
            'grade_id',
            'stream_id',
            'created_at',
            'updated_at',
        ])
        ->with([
            'grade:id,name',
            'stream:id,name,slug',
        ])
        ->withCount('chapters');

        if ($request->filled('grade_id')) {
            $query->where('grade_id', (int) $request->query('grade_id'));
        }

        if ($request->filled('stream_id')) {
            $streamIdInt = (int) $request->query('stream_id');
            $query->where(function ($q) use ($streamIdInt) {
                $q->where('stream_id', $streamIdInt)
                  ->orWhereNull('stream_id');
            });
        }

        if ($request->filled('q')) {
            $searchTerm = strtolower(trim($request->query('q')));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        // Include chapters list ordered by chapter_number if requested or default true for admin UI
        if ($request->boolean('with_chapters', true)) {
            $query->with(['chapters' => function ($q) {
                $q->select([
                    'id',
                    'chapter_number',
                    'title',
                    'description',
                    'subject_id',
                    'grade_id',
                    'is_featured',
                    'pdf_path',
                    'notes_generated_at',
                    'created_at',
                ])
                ->selectRaw("CASE WHEN ai_generated_notes IS NOT NULL AND TRIM(ai_generated_notes) != '' THEN 1 ELSE 0 END as has_ai_notes")
                ->orderBy('chapter_number', 'asc');
            }]);
        }

        $subjects = $query->orderBy('grade_id', 'asc')->orderBy('title', 'asc')->get();

        $subjects->each(function ($s) {
            $s->stream_slug = $s->stream_id === 1 ? 'natural' : ($s->stream_id === 2 ? 'social' : 'common');
            $s->stream_name = $s->stream_id === 1 ? 'Natural Science' : ($s->stream_id === 2 ? 'Social Science' : 'Common Core');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Admin subjects retrieved successfully',
            'data' => $subjects,
        ]);
    }

    /**
     * Display the specified subject by ID with full details and sorted chapters.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $subject = Subject::with([
            'grade',
            'stream',
            'chapters' => function ($q) {
                $q->orderBy('chapter_number', 'asc');
            },
        ])->withCount('chapters')->find($id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subject not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Subject retrieved successfully',
            'data' => $subject,
        ]);
    }

    /**
     * Return all subjects specifically for a given grade ID.
     *
     * @param int|string $gradeId
     * @return JsonResponse
     */
    public function getByGrade($gradeId): JsonResponse
    {
        $grade = Grade::find($gradeId);
        if (!$grade) {
            return response()->json([
                'status' => 'error',
                'message' => 'Grade not found',
            ], 404);
        }

        $subjects = Subject::where('grade_id', $gradeId)
            ->with([
                'grade',
                'stream',
                'chapters' => function ($q) {
                    $q->orderBy('chapter_number', 'asc');
                },
            ])
            ->withCount('chapters')
            ->orderBy('title', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => "Subjects for grade {$gradeId} retrieved successfully",
            'grade' => $grade,
            'data' => $subjects,
        ]);
    }

    /**
     * Search subjects by query term with optional grade_id and stream_id filtering.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string', 'max:255'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'stream_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Subject::with('chapters');

        if ($request->filled('q')) {
            $searchTerm = strtolower(trim($request->query('q')));
            $query->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%']);
        }

        if ($request->filled('grade_id')) {
            $query->where('grade_id', (int) $request->query('grade_id'));
        }

        if ($request->filled('stream_id')) {
            $streamIdInt = (int) $request->query('stream_id');
            $query->where(function ($q) use ($streamIdInt) {
                $q->where('stream_id', $streamIdInt)
                  ->orWhereNull('stream_id');
            });
        }

        $subjects = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Subjects retrieved successfully',
            'data' => $subjects,
        ]);
    }

    /**
     * Get top 3 recently viewed subjects (from session or padded to 3 active subjects).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentlyViewed(Request $request): JsonResponse
    {
        $recentIds = session()->get('recently_viewed_subjects', []);
        $subjects = collect();

        if (!empty($recentIds) && is_array($recentIds)) {
            $fetched = Subject::with('chapters')->whereIn('id', $recentIds)->get();
            $subjects = $fetched->sortBy(function ($subject) use ($recentIds) {
                return array_search($subject->id, $recentIds);
            })->take(3)->values();
        }

        if ($subjects->count() < 3) {
            $existingIds = $subjects->pluck('id')->all();
            $fallback = Subject::with('chapters')
                ->whereNotIn('id', $existingIds)
                ->take(3 - $subjects->count())
                ->get();
            $subjects = $subjects->concat($fallback)->take(3)->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $subjects,
        ]);
    }

    /**
     * Record a subject as recently viewed in session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordRecentlyViewed(Request $request): JsonResponse
    {
        $recent = session()->get('recently_viewed_subjects', []);
        if (!is_array($recent)) {
            $recent = [];
        }

        $subjectId = $request->input('subject_id') ?? $request->input('id');

        if ($subjectId) {
            $recent = array_values(array_unique(array_merge([(int) $subjectId], $recent)));
            session()->put('recently_viewed_subjects', array_slice($recent, 0, 10));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Recorded successfully',
        ]);
    }

    /**
     * Store a newly created subject (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Accept either title or name from the frontend
        $title = $request->input('title') ?? $request->input('name');

        // Resolve stream_id: 1 for Natural Science, 2 for Social Science, null for Common Core
        $streamId = $request->input('stream_id');
        if ($streamId === 'null' || $streamId === '' || $streamId === 0 || $streamId === '0') {
            $streamId = null;
        }

        $streamParam = $request->input('stream');
        if ($streamParam !== null) {
            $slug = strtolower(trim((string)$streamParam));
            if ($slug === 'natural') {
                $streamId = 1;
            } elseif ($slug === 'social') {
                $streamId = 2;
            } elseif ($slug === 'common' || $slug === 'null' || $slug === 'all' || $slug === '') {
                $streamId = null;
            }
        } elseif (is_string($streamId) && !is_numeric($streamId)) {
            $slug = strtolower(trim($streamId));
            if (str_contains($slug, 'nat')) {
                $streamId = 1;
            } elseif (str_contains($slug, 'soc')) {
                $streamId = 2;
            } else {
                $streamId = null;
            }
        } elseif ($streamId !== null) {
            $streamId = (int)$streamId;
            if ($streamId !== 1 && $streamId !== 2) {
                $stream = Stream::find($streamId);
                if ($stream) {
                    $lowerStream = strtolower($stream->name);
                    $streamId = str_contains($lowerStream, 'nat') ? 1 : (str_contains($lowerStream, 'soc') ? 2 : null);
                } else {
                    $streamId = null;
                }
            }
        }

        $validator = Validator::make(array_merge($request->all(), [
            'resolved_title' => $title,
            'resolved_stream_id' => $streamId,
        ]), [
            'grade_id' => 'required|exists:grades,id',
            'resolved_title' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'stream_id' => 'nullable',
            'resolved_stream_id' => 'nullable|integer|exists:streams,id',
        ], [
            'resolved_title.required' => 'The subject title or name field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $grade = Grade::find($request->input('grade_id'));
        if ($grade && method_exists($grade, 'supportsLearning') && !$grade->supportsLearning()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected grade does not support normal learning content.',
                'errors' => [
                    'grade_id' => ['The selected grade cannot be associated with normal learning content.'],
                ],
            ], 422);
        }

        $createData = [
            'grade_id' => (int) $request->input('grade_id'),
            'title' => trim((string)$title),
            'description' => $request->input('description') ?? null,
            'stream_id' => $streamId,
        ];

        if (Schema::hasColumn('subjects', 'name')) {
            $createData['name'] = trim((string)$title);
        }

        $subject = Subject::create($createData);

        // Record audit trail
        AuditLogService::log('subject.created', $subject, null, [
            'id' => $subject->id,
            'title' => $subject->title,
            'grade_id' => $subject->grade_id,
            'stream_id' => $subject->stream_id,
        ]);

        // Clear cache immediately
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }
        if (class_exists(CurriculumCacheService::class)) {
            CurriculumCacheService::clearForSubject($subject->grade_id, $subject->id);
        }

        $freshSubject = $subject->load(['grade', 'stream', 'chapters']);
        $freshSubject->stream_slug = $freshSubject->stream_id === 1 ? 'natural' : ($freshSubject->stream_id === 2 ? 'social' : 'common');
        $freshSubject->stream_name = $freshSubject->stream_id === 1 ? 'Natural Science' : ($freshSubject->stream_id === 2 ? 'Social Science' : 'Common Core');

        return response()->json([
            'status' => 'success',
            'message' => 'Subject created successfully.',
            'data' => $freshSubject,
        ], 201);
    }

    /**
     * Update the specified subject (Admin only).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subject not found',
            ], 404);
        }

        $oldValues = [
            'title' => $subject->title,
            'grade_id' => $subject->grade_id,
            'stream_id' => $subject->stream_id,
        ];

        $title = $request->input('title') ?? $request->input('name');

        // Resolve stream_id: 1 for Natural Science, 2 for Social Science, null for Common Core
        $streamId = $request->input('stream_id');
        if ($streamId === 'null' || $streamId === '' || $streamId === 0 || $streamId === '0') {
            $streamId = null;
        } elseif ($streamId !== null) {
            $streamId = (int)$streamId;
        } elseif ($request->has('stream')) {
            $streamVal = strtolower((string)$request->input('stream'));
            if ($streamVal === 'natural' || $streamVal === '1') {
                $streamId = 1;
            } elseif ($streamVal === 'social' || $streamVal === '2') {
                $streamId = 2;
            } else {
                $streamId = null;
            }
        }

        if ($request->has('grade_id')) {
            $grade = Grade::find($request->input('grade_id'));
            if ($grade && method_exists($grade, 'supportsLearning') && !$grade->supportsLearning()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected grade does not support normal learning content.',
                    'errors' => [
                        'grade_id' => ['The selected grade cannot be associated with normal learning content.'],
                    ],
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'grade_id' => 'sometimes|integer|exists:grades,id',
            'stream_id' => 'nullable',
            'title' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldGradeId = $subject->grade_id;

        $updateData = [];
        if ($request->has('grade_id')) {
            $updateData['grade_id'] = (int)$request->input('grade_id');
        }
        if ($title !== null) {
            $updateData['title'] = trim((string)$title);
            if (Schema::hasColumn('subjects', 'name')) {
                $updateData['name'] = trim((string)$title);
            }
        }
        if ($request->has('description')) {
            $updateData['description'] = $request->input('description');
        }
        if ($request->has('stream_id') || $request->has('stream')) {
            $updateData['stream_id'] = $streamId;
        }

        $subject->update($updateData);

        // Record audit trail
        AuditLogService::log('subject.updated', $subject, $oldValues, [
            'title' => $subject->title,
            'grade_id' => $subject->grade_id,
            'stream_id' => $subject->stream_id,
        ]);

        // Invalidate cache immediately
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }
        if (class_exists(CurriculumCacheService::class)) {
            CurriculumCacheService::clearForSubject($oldGradeId, $subject->id);
            if ($subject->grade_id !== $oldGradeId) {
                CurriculumCacheService::clearForSubject($subject->grade_id, $subject->id);
            }
        }

        $freshSubject = $subject->fresh(['grade', 'stream', 'chapters']);
        $freshSubject->stream_slug = $freshSubject->stream_id === 1 ? 'natural' : ($freshSubject->stream_id === 2 ? 'social' : 'common');
        $freshSubject->stream_name = $freshSubject->stream_id === 1 ? 'Natural Science' : ($freshSubject->stream_id === 2 ? 'Social Science' : 'Common Core');

        return response()->json([
            'status' => 'success',
            'message' => 'Subject updated successfully.',
            'data' => $freshSubject,
        ]);
    }

    /**
     * Delete the specified subject (Admin only).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => 'Subject not found',
            ], 404);
        }

        $gradeId = $subject->grade_id;
        $subjectId = $subject->id;
        $deletedSnapshot = [
            'id' => $subject->id,
            'title' => $subject->title,
            'grade_id' => $gradeId,
            'stream_id' => $subject->stream_id,
        ];

        $subject->delete();

        // Record audit trail
        AuditLogService::log('subject.deleted', 'App\Models\Subject', $deletedSnapshot, null);

        // Invalidate all related caches
        CurriculumCacheService::clearForSubject($gradeId, $subjectId);

        return response()->json([
            'status' => 'success',
            'message' => 'Subject deleted successfully',
        ]);
    }
}


