<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\Textbook;
use App\Models\Video;
use App\Jobs\GenerateChapterNotesJob;
use App\Jobs\GenerateChapterQuizJob;
use App\Jobs\GenerateCumulativePremiumNotesJob;
use App\Jobs\GenerateCumulativePremiumQuizJob;
use App\Models\ChapterPremiumVariant;
use App\Services\GeminiService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChapterController extends Controller
{
    /**
     * Display a listing of chapters with optional filtering by grade_id and/or subject_id.
     * Caches query results for high performance.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $gradeId = $request->query('grade_id');
        $subjectId = $request->query('subject_id');

        $cacheKey = 'api_chapters_g' . ($gradeId ?? 'all') . '_s' . ($subjectId ?? 'all');

        $chapters = Cache::remember($cacheKey, 3600, function () use ($gradeId, $subjectId) {
            $query = Chapter::with(['grade', 'subject']);

            if ($gradeId !== null) {
                $query->where('grade_id', $gradeId);
            }

            if ($subjectId !== null) {
                $query->where('subject_id', $subjectId);
            }

            return $query->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Chapters retrieved successfully',
            'data' => $chapters,
        ]);
    }

    /**
     * Display a listing of chapters for a specific grade.
     *
     * @param int|string $gradeId
     * @return JsonResponse
     */
    public function getByGrade($gradeId): JsonResponse
    {
        $chapters = Cache::remember('api_chapters_grade_' . $gradeId, 3600, function () use ($gradeId) {
            return Chapter::where('grade_id', $gradeId)
                ->with(['grade', 'subject'])
                ->orderBy('subject_id', 'asc')
                ->orderBy('chapter_number', 'asc')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Chapters retrieved successfully',
            'data' => $chapters,
        ]);
    }

    /**
     * Resolve the active note for a chapter or automatically synthesize and persist an AI note on-demand.
     *
     * @param Chapter $chapter
     * @return object|null
     */
    public static function resolveOrGenerateActiveNote(Chapter $chapter): ?object
    {
        // 1. Try to find the active note chosen by the admin
        $activeNote = DB::table('notes')
            ->where('chapter_id', $chapter->id)
            ->where('is_active', 1)
            ->first();

        // 2. If no note is explicitly marked active, check for any existing note
        if (!$activeNote) {
            $activeNote = DB::table('notes')
                ->where('chapter_id', $chapter->id)
                ->orderBy('id', 'desc')
                ->first();
        }

        // 3. IF ZERO NOTES EXIST: Auto-generate with AI on-the-fly!
        if (!$activeNote) {
            try {
                $subjectName = $chapter->subject->title ?? $chapter->subject->name ?? 'Subject';
                $gradeName = $chapter->subject->grade->name ?? $chapter->grade->name ?? 'Grade';
                $cleanTitle = "{$chapter->title} Notes";

                $content = null;

                // Invoke the AI generator service
                try {
                    $serviceClass = class_exists(\App\Services\AiService::class)
                        ? \App\Services\AiService::class
                        : (class_exists(\App\Services\GeminiService::class) ? \App\Services\GeminiService::class : null);

                    $aiService = $serviceClass ? app($serviceClass) : null;

                    if ($aiService && method_exists($aiService, 'generateChapterNotes')) {
                        $content = $aiService->generateChapterNotes($chapter);
                    }
                } catch (\Throwable $aiErr) {
                    \Illuminate\Support\Facades\Log::warning("AI note generation service error for chapter {$chapter->id}, using structured fallback: " . $aiErr->getMessage());
                }

                if (empty($content)) {
                    // High-yield structured pedagogical fallback
                    $content = "# {$chapter->title}\n\n"
                        . "**Subject:** {$subjectName} • **Grade:** {$gradeName}\n\n"
                        . "## 1. Key Conceptual Overview\n"
                        . "- Comprehensive study overview for {$chapter->title}.\n"
                        . "- Core definitions, principles, and concepts aligned with the official national syllabus.\n\n"
                        . "## 2. Key Formulas & Derivations\n"
                        . "```text\n"
                        . "// Governing equations and principles\n"
                        . "Formula: Fundamental principles of {$chapter->title}\n"
                        . "```\n\n"
                        . "## 3. High-Yield Examination Takeaways\n"
                        . "1. Key concepts frequently tested in national entrance exams.\n"
                        . "2. Step-by-step problem-solving techniques.\n\n"
                        . "---\n*Synthesized automatically by Asteghi AI Curriculum Engine.*";
                }

                // Persist to MySQL immediately
                $gradeId = $chapter->grade_id ?? ($chapter->subject ? $chapter->subject->grade_id : null);
                $newNoteId = DB::table('notes')->insertGetId([
                    'chapter_id' => $chapter->id,
                    'grade_id' => $gradeId,
                    'subject_id' => $chapter->subject_id ?? null,
                    'title' => $cleanTitle,
                    'content' => $content,
                    'is_ai_generated' => 1,
                    'is_active' => 1, // Visible by default
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $activeNote = DB::table('notes')->where('id', $newNoteId)->first();

                // Keep chapter model synchronized
                DB::table('chapters')->where('id', $chapter->id)->update([
                    'ai_generated_notes' => $content,
                    'notes_generated_at' => now(),
                ]);
                $chapter->ai_generated_notes = $content;
                $chapter->notes_generated_at = now();

                // Clear cache so next request hits DB
                Cache::flush();
                if (class_exists(\App\Helpers\CurriculumCacheHelper::class)) {
                    \App\Helpers\CurriculumCacheHelper::clearAllCurriculumCache();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("On-demand AI generation failed for chapter {$chapter->id}: " . $e->getMessage());
            }
        }

        return $activeNote;
    }

    /**
     * Display the specified chapter with grade and subject information.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $chapter = Chapter::with(['grade', 'subject', 'subject.grade'])->find($id);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        // Retrieve the active note or generate on-demand if zero notes exist
        $activeNote = self::resolveOrGenerateActiveNote($chapter);

        // Dynamically reflect active note content into ai_generated_notes for student UI
        if ($activeNote) {
            $chapter->ai_generated_notes = $activeNote->content;
        }

        // Attach the active note to the chapter response
        $chapter->active_note = $activeNote;
        $chapter->setRelation('notes', $activeNote ? collect([$activeNote]) : collect([]));

        return response()->json([
            'status' => 'success',
            'message' => 'Chapter retrieved successfully',
            'data' => $chapter,
        ]);
    }

    /**
     * Display a listing of chapters for a specific subject.
     *
     * @param int|string $subjectId
     * @return JsonResponse
     */
    public function getBySubject($subjectId): JsonResponse
    {
        $chapters = Cache::remember('api_chapters_subject_' . $subjectId, 3600, function () use ($subjectId) {
            return Chapter::where('subject_id', $subjectId)
                ->with(['grade', 'subject'])
                ->orderBy('chapter_number', 'asc')
                ->get();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Chapters retrieved successfully',
            'data' => $chapters,
        ]);
    }
    /**
     * Display all learning content belonging to a specific chapter.
     * Safely triggers on-demand AI note generation with cache stampede protection.
     *
     * @param int|string|Request $chapterIdOrRequest
     * @param int|string|null $possibleChapterId
     * @return JsonResponse
     */
    public function getContent($chapterIdOrRequest, $possibleChapterId = null): JsonResponse
    {
        if ($chapterIdOrRequest instanceof Request) {
            $request = $chapterIdOrRequest;
            $chapterId = $possibleChapterId;
        } else {
            $request = request();
            $chapterId = $chapterIdOrRequest;
        }

        $chapter = Chapter::with(['grade', 'subject', 'subject.grade', 'textbooks'])->find($chapterId);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        $videos = Video::where('chapter_id', $chapterId)->get();

        // Retrieve the single active note or generate on-demand if zero notes exist
        $activeNote = self::resolveOrGenerateActiveNote($chapter);
        $notes = $activeNote ? collect([$activeNote]) : collect([]);

        $textbooks = Textbook::where('chapter_id', $chapterId)->get();
        $resources = Resource::where('chapter_id', $chapterId)->get();
        $quizzes = Quiz::where('chapter_id', $chapterId)->where('is_premium', false)->with('questions')->get();

        $effectiveNotesContent = $activeNote ? $activeNote->content : $chapter->ai_generated_notes;
        $hasNotes = !empty(trim((string) $effectiveNotesContent));

        $userResourceIds = $request->user() ? $request->user()->getUnlockedResourceIdsForChapter($chapter->id) : [];
        $sortedResourceIds = array_values(array_unique(array_map('intval', $userResourceIds)));
        sort($sortedResourceIds);
        $signature = !empty($sortedResourceIds) ? ('res_' . implode('_', $sortedResourceIds)) : null;

        $hasPaid = !empty($signature);

        // Resource masking based on specific user unlocked resources
        $formattedResources = $resources->map(function ($res) use ($sortedResourceIds, $chapter) {
            $resArray = $res->toArray();
            $isUnlocked = in_array((int) $res->id, $sortedResourceIds, true);
            if (!$isUnlocked) {
                $resArray['url'] = null;
                $resArray['file_url'] = null;
                $resArray['download_url'] = null;
                $resArray['is_locked'] = true;
            } else {
                $resArray['is_locked'] = false;
                $resArray['download_url'] = url("/api/chapters/{$chapter->id}/resources/{$res->id}/download");
            }
            return $resArray;
        });

        // Cumulative Premium notes status and background trigger
        $premiumNotesStatus = 'locked';
        $aiPremiumNotes = null;
        $premiumNotesGeneratedAt = null;

        if ($signature !== null) {
            $variant = ChapterPremiumVariant::where('chapter_id', $chapter->id)
                ->where('resource_signature', $signature)
                ->first();

            if ($variant && !empty(trim((string) $variant->ai_premium_notes))) {
                $premiumNotesStatus = 'ready';
                $aiPremiumNotes = $variant->ai_premium_notes;
                $premiumNotesGeneratedAt = $variant->generated_at;
            } else {
                $failedNotesKey = "premium_notes_failed_{$chapter->id}_{$signature}";
                if (Cache::has($failedNotesKey)) {
                    $premiumNotesStatus = 'not_available';
                } else {
                    $premiumLockKey = "generating_premium_notes_{$chapter->id}_{$signature}";
                    if (Cache::has($premiumLockKey)) {
                        $premiumNotesStatus = 'generating';
                    } else {
                        Cache::put($premiumLockKey, true, now()->addMinutes(3));
                        GenerateCumulativePremiumNotesJob::dispatch($chapter, $sortedResourceIds);
                        $premiumNotesStatus = 'generating';
                    }
                }
            }
        }

        $chapterData = [
            'id' => $chapter->id,
            'name' => $chapter->title,
            'title' => $chapter->title,
            'chapter_number' => $chapter->chapter_number,
            'description' => $chapter->description,
            'pdf_path' => $chapter->pdf_path,
            'ai_generated_notes' => $effectiveNotesContent,
            'ai_generated_notes_html' => $effectiveNotesContent ? Str::markdown($effectiveNotesContent) : null,
            'notes_generated_at' => $activeNote ? $activeNote->updated_at : $chapter->notes_generated_at,
            'active_note' => $activeNote,
            'has_premium_access' => $hasPaid,
            'premium_notes_status' => $premiumNotesStatus,
            'ai_premium_notes' => $aiPremiumNotes,
            'ai_premium_notes_html' => $aiPremiumNotes ? Str::markdown($aiPremiumNotes) : null,
            'premium_notes_generated_at' => $premiumNotesGeneratedAt,
            'resource_signature' => $signature,
            'unlocked_resource_ids' => $sortedResourceIds,
            'grade_id' => $chapter->grade_id,
            'subject_id' => $chapter->subject_id,
            'grade' => $chapter->grade,
            'subject' => $chapter->subject,
        ];

        // Case A: Notes already exist
        if ($hasNotes) {
            return response()->json([
                'status' => 'success',
                'message' => 'Chapter content retrieved successfully',
                'data' => [
                    'chapter' => array_merge($chapterData, ['notes_status' => 'ready']),
                    'notes_status' => 'ready',
                    'has_premium_access' => $hasPaid,
                    'premium_notes_status' => $premiumNotesStatus,
                    'ai_premium_notes' => $aiPremiumNotes,
                    'resource_signature' => $signature,
                    'videos' => $videos,
                    'notes' => $notes,
                    'textbooks' => $textbooks,
                    'resources' => $formattedResources,
                    'quizzes' => $quizzes,
                ],
            ], 200);
        }

        // Case B: Check for recent failure lock
        $failedNotesKey = "notes_failed_chapter_{$chapter->id}";
        if (Cache::has($failedNotesKey)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Note generation previously failed for this chapter. Please contact support or notify admin.',
                'data' => [
                    'chapter' => array_merge($chapterData, ['notes_status' => 'not_available']),
                    'notes_status' => 'not_available',
                    'has_premium_access' => $hasPaid,
                    'premium_notes_status' => $premiumNotesStatus,
                    'ai_premium_notes' => $aiPremiumNotes,
                    'resource_signature' => $signature,
                    'videos' => $videos,
                    'notes' => $notes,
                    'textbooks' => $textbooks,
                    'resources' => $formattedResources,
                    'quizzes' => $quizzes,
                ],
            ], 200);
        }

        // Case C: Check physical PDF existence on server disk
        $pdfPath = $chapter->resolvePdfPath();
        if (!$pdfPath) {
            return response()->json([
                'status' => 'success',
                'message' => 'No physical textbook PDF found on server for this chapter.',
                'data' => [
                    'chapter' => array_merge($chapterData, ['notes_status' => 'not_available']),
                    'notes_status' => 'not_available',
                    'has_premium_access' => $hasPaid,
                    'premium_notes_status' => $premiumNotesStatus,
                    'ai_premium_notes' => $aiPremiumNotes,
                    'resource_signature' => $signature,
                    'videos' => $videos,
                    'notes' => $notes,
                    'textbooks' => $textbooks,
                    'resources' => $formattedResources,
                    'quizzes' => $quizzes,
                ],
            ], 200);
        }

        // Case D: Notes missing, physical PDF available - Stampede lock check
        $lockKey = "generating_notes_chapter_{$chapter->id}";

        if (Cache::has($lockKey)) {
            // Another student already triggered it; return waiting status
            return response()->json([
                'status' => 'success',
                'message' => 'AI is currently preparing notes for this chapter.',
                'data' => [
                    'chapter' => array_merge($chapterData, ['notes_status' => 'generating']),
                    'notes_status' => 'generating',
                    'has_premium_access' => $hasPaid,
                    'premium_notes_status' => $premiumNotesStatus,
                    'ai_premium_notes' => $aiPremiumNotes,
                    'resource_signature' => $signature,
                    'retry_after_seconds' => 10,
                    'videos' => $videos,
                    'notes' => $notes,
                    'textbooks' => $textbooks,
                    'resources' => $formattedResources,
                    'quizzes' => $quizzes,
                ],
            ], 200);
        }

        // Acquire 3-minute lock to prevent stampedes and dispatch background worker
        Cache::put($lockKey, true, now()->addMinutes(3));
        GenerateChapterNotesJob::dispatch($chapter);

        return response()->json([
            'status' => 'success',
            'message' => 'AI note generation started for this chapter. Check back shortly.',
            'data' => [
                'chapter' => array_merge($chapterData, ['notes_status' => 'generating']),
                'notes_status' => 'generating',
                'has_premium_access' => $hasPaid,
                'premium_notes_status' => $premiumNotesStatus,
                'ai_premium_notes' => $aiPremiumNotes,
                'resource_signature' => $signature,
                'retry_after_seconds' => 15,
                'videos' => $videos,
                'notes' => $notes,
                'textbooks' => $textbooks,
                'resources' => $formattedResources,
                'quizzes' => $quizzes,
            ],
        ], 200);
    }
    
    public function getFeatured(): \Illuminate\Http\JsonResponse
    {
        $chapters = \App\Models\Chapter::where('is_featured', true)->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $chapters
        ], 200);
    }

    /**
     * Dispatch Google Gemini background job to generate chapter notes from textbook PDF (or premium synthesis).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function generateNotes(Request $request, $id): JsonResponse
    {
        $chapter = Chapter::with(['grade', 'subject', 'textbooks', 'resources'])->find($id);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        $isPremium = strtolower((string) $request->input('type', $request->query('type', 'standard'))) === 'premium';

        if ($isPremium) {
            $requestedResourceIds = $request->input('resource_ids');
            if (!empty($requestedResourceIds) && is_array($requestedResourceIds)) {
                $sortedResourceIds = array_values(array_unique(array_map('intval', $requestedResourceIds)));
                sort($sortedResourceIds);
                $signature = 'res_' . implode('_', $sortedResourceIds);

                $existingVariant = ChapterPremiumVariant::where('chapter_id', $chapter->id)
                    ->where('resource_signature', $signature)
                    ->first();

                if ($existingVariant && !empty(trim((string)$existingVariant->ai_premium_notes)) && !$request->boolean('force') && !$request->boolean('overwrite')) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Cumulative premium notes already exist for this variant. Pass force=true or overwrite=true to regenerate.',
                        'data' => [
                            'chapter_id' => $chapter->id,
                            'title' => $chapter->title,
                            'is_premium' => true,
                            'resource_signature' => $signature,
                            'ai_premium_notes' => $existingVariant->ai_premium_notes,
                            'ai_premium_notes_html' => Str::markdown($existingVariant->ai_premium_notes),
                            'premium_notes_generated_at' => $existingVariant->generated_at,
                            'already_exists' => true,
                        ]
                    ]);
                }

                Cache::forget("premium_notes_failed_{$chapter->id}_{$signature}");

                if ($request->boolean('sync')) {
                    try {
                        $job = new GenerateCumulativePremiumNotesJob($chapter, $sortedResourceIds);
                        $job->handle(app(GeminiService::class));
                        $variant = ChapterPremiumVariant::where('chapter_id', $chapter->id)
                            ->where('resource_signature', $signature)
                            ->first();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Cumulative premium notes generated synchronously.',
                            'data' => [
                                'chapter_id' => $chapter->id,
                                'title' => $chapter->title,
                                'is_premium' => true,
                                'resource_signature' => $signature,
                                'ai_premium_notes' => $variant ? $variant->ai_premium_notes : null,
                                'ai_premium_notes_html' => ($variant && $variant->ai_premium_notes) ? Str::markdown($variant->ai_premium_notes) : null,
                                'premium_notes_generated_at' => $variant ? $variant->generated_at : null,
                            ]
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Failed generating cumulative premium notes: ' . $e->getMessage(),
                        ], 500);
                    }
                }

                GenerateCumulativePremiumNotesJob::dispatch($chapter, $sortedResourceIds);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Gemini AI is generating cumulative premium study notes in the background.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'title' => $chapter->title,
                        'is_premium' => true,
                        'resource_signature' => $signature,
                        'status' => 'processing',
                    ]
                ]);
            }

            $resourcePdf = $chapter->resolveResourcePdfPath();
            if (!$resourcePdf) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No supplementary resource PDF file attached to this chapter.',
                ], 422);
            }

            if (!empty($chapter->ai_premium_notes) && !$request->boolean('force') && !$request->boolean('overwrite')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Premium notes already exist for this chapter. Pass force=true or overwrite=true to regenerate.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'title' => $chapter->title,
                        'is_premium' => true,
                        'ai_premium_notes' => $chapter->ai_premium_notes,
                        'ai_premium_notes_html' => Str::markdown($chapter->ai_premium_notes),
                        'premium_notes_generated_at' => $chapter->premium_notes_generated_at,
                        'already_exists' => true,
                    ]
                ]);
            }

            Cache::forget("premium_notes_failed_chapter_{$chapter->id}");

            if ($request->boolean('sync')) {
                try {
                    $job = new GenerateChapterNotesJob($chapter);
                    $job->handle(app(\App\Services\GeminiService::class));
                    $chapter->refresh();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Premium notes generated synchronously.',
                        'data' => [
                            'chapter_id' => $chapter->id,
                            'title' => $chapter->title,
                            'is_premium' => true,
                            'ai_premium_notes' => $chapter->ai_premium_notes,
                            'ai_premium_notes_html' => Str::markdown($chapter->ai_premium_notes ?? ''),
                            'premium_notes_generated_at' => $chapter->premium_notes_generated_at,
                        ]
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed generating premium notes: ' . $e->getMessage(),
                    ], 500);
                }
            }

            GenerateChapterNotesJob::dispatch($chapter);

            return response()->json([
                'status' => 'success',
                'message' => 'Gemini AI is generating premium study notes in the background.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'is_premium' => true,
                    'status' => 'processing',
                ]
            ]);
        }

        // Check if PDF exists on chapter or textbook relationship
        $hasTextbook = $chapter->pdf_path || $chapter->textbooks()->count() > 0;
        if (!$hasTextbook) {
            return response()->json([
                'status' => 'error',
                'message' => 'No PDF file attached to this chapter or its associated textbooks.',
            ], 422);
        }

        // Return existing notes if already generated and force/overwrite is not requested
        if (!empty($chapter->ai_generated_notes) && !$request->boolean('force') && !$request->boolean('overwrite')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notes already exist for this chapter. Pass force=true or overwrite=true to regenerate.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'ai_generated_notes' => $chapter->ai_generated_notes,
                    'ai_generated_notes_html' => Str::markdown($chapter->ai_generated_notes),
                    'notes_generated_at' => $chapter->notes_generated_at,
                    'already_exists' => true,
                ]
            ]);
        }

        // Clear any previous failure lock for this chapter
        Cache::forget("notes_failed_chapter_{$chapter->id}");

        // If request asks for sync execution (e.g. testing)
        if ($request->boolean('sync')) {
            try {
                $job = new GenerateChapterNotesJob($chapter);
                $job->handle(app(\App\Services\GeminiService::class));
                $chapter->refresh();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Notes generated synchronously.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'title' => $chapter->title,
                        'ai_generated_notes' => $chapter->ai_generated_notes,
                        'ai_generated_notes_html' => Str::markdown($chapter->ai_generated_notes ?? ''),
                        'notes_generated_at' => $chapter->notes_generated_at,
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed generating notes: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Dispatch background job
        GenerateChapterNotesJob::dispatch($chapter);

        return response()->json([
            'status' => 'success',
            'message' => 'Gemini AI is generating study notes in the background.',
            'data' => [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'status' => 'processing',
            ]
        ]);
    }

    /**
     * Dispatch Google Gemini background job to generate chapter quiz questions (standard or premium).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function generateQuiz(Request $request, $id): JsonResponse
    {
        $chapter = Chapter::with(['grade', 'subject', 'resources'])->find($id);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        $isPremium = strtolower((string) $request->input('type', $request->query('type', 'standard'))) === 'premium';
        $shouldRegenerate = $request->boolean('force') || $request->boolean('overwrite') || $request->boolean('regenerate');

        if ($isPremium) {
            $requestedResourceIds = $request->input('resource_ids');
            if (!empty($requestedResourceIds) && is_array($requestedResourceIds)) {
                $sortedResourceIds = array_values(array_unique(array_map('intval', $requestedResourceIds)));
                sort($sortedResourceIds);
                $signature = 'res_' . implode('_', $sortedResourceIds);

                $existingQuiz = Quiz::where('chapter_id', $chapter->id)
                    ->where('is_premium', true)
                    ->where('resource_signature', $signature)
                    ->first();

                if ($existingQuiz && !$shouldRegenerate) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Cumulative premium quiz already exists for this variant. Pass force=true, overwrite=true, or regenerate=true to replace with a new AI quiz.',
                        'data' => [
                            'chapter_id' => $chapter->id,
                            'quiz_id' => $existingQuiz->id,
                            'title' => $existingQuiz->title,
                            'is_premium' => true,
                            'resource_signature' => $signature,
                            'already_exists' => true,
                        ]
                    ]);
                }

                Cache::forget("premium_quiz_failed_{$chapter->id}_{$signature}");

                if ($request->boolean('sync')) {
                    try {
                        $job = new GenerateCumulativePremiumQuizJob($chapter, $sortedResourceIds);
                        $job->handle(app(GeminiService::class));

                        $quiz = Quiz::where('chapter_id', $chapter->id)
                            ->where('is_premium', true)
                            ->where('resource_signature', $signature)
                            ->with('questions')
                            ->first();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Cumulative premium quiz generated synchronously.',
                            'data' => $quiz,
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Failed generating cumulative premium quiz: ' . $e->getMessage(),
                        ], 500);
                    }
                }

                GenerateCumulativePremiumQuizJob::dispatch($chapter, $sortedResourceIds);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Gemini AI is generating cumulative premium quiz questions in the background.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'title' => $chapter->title,
                        'is_premium' => true,
                        'resource_signature' => $signature,
                        'status' => 'processing',
                    ]
                ]);
            }

            $hasSource = (strlen(trim((string) $chapter->ai_premium_notes)) >= 200)
                || ((bool) $chapter->resolveResourcePdfPath() && (bool) $chapter->resolvePdfPath());

            if (!$hasSource) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No premium notes or supplementary resource PDF available for this chapter.',
                ], 422);
            }

            $existingQuiz = Quiz::where('chapter_id', $chapter->id)->where('is_premium', true)->first();

            if ($existingQuiz && !$shouldRegenerate) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Premium quiz already exists for this chapter. Pass force=true, overwrite=true, or regenerate=true to replace with a new AI quiz.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'quiz_id' => $existingQuiz->id,
                        'title' => $existingQuiz->title,
                        'is_premium' => true,
                        'already_exists' => true,
                    ]
                ]);
            }

            Cache::forget("premium_quiz_failed_chapter_{$chapter->id}");

            if ($request->boolean('sync')) {
                try {
                    $job = new GenerateChapterQuizJob($chapter);
                    $job->handle(app(\App\Services\GeminiService::class));

                    $quiz = Quiz::where('chapter_id', $chapter->id)->where('is_premium', true)->with('questions')->first();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Premium quiz generated synchronously.',
                        'data' => $quiz,
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed generating premium quiz: ' . $e->getMessage(),
                    ], 500);
                }
            }

            GenerateChapterQuizJob::dispatch($chapter);

            return response()->json([
                'status' => 'success',
                'message' => 'Gemini AI is generating premium quiz questions in the background.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'title' => $chapter->title,
                    'is_premium' => true,
                    'status' => 'processing',
                ]
            ]);
        }

        // Support appending questions directly without full quiz overwrite
        if ($request->boolean('append')) {
            $targetQuizId = $request->input('quiz_id');
            $quiz = ($targetQuizId && is_numeric($targetQuizId) && $targetQuizId < 1000000000) ? Quiz::find($targetQuizId) : null;
            if (!$quiz) {
                $quiz = Quiz::where('chapter_id', $chapter->id)
                    ->where(function ($q) use ($isPremium) {
                        $q->where('is_premium', $isPremium);
                    })
                    ->first();
            }
            if (!$quiz) {
                $quiz = Quiz::create([
                    'chapter_id' => $chapter->id,
                    'grade_id' => $chapter->grade_id,
                    'subject_id' => $chapter->subject_id,
                    'title' => ($isPremium ? 'AI Premium Quiz: ' : 'AI Quiz: ') . $chapter->title,
                    'description' => 'Chapter quiz generated automatically by AI.',
                    'is_premium' => $isPremium,
                ]);
            }

            return app(AdminLearningController::class)->generateQuizQuestions($request, $quiz->id);
        }

        // Verify chapter has AI generated notes or fallback to intelligent synthesis on overwrite/force
        if (empty($chapter->ai_generated_notes)) {
            if ($shouldRegenerate || $request->boolean('overwrite') || $request->boolean('force') || $request->boolean('sync')) {
                $quiz = Quiz::where('chapter_id', $chapter->id)
                    ->where(function ($q) use ($isPremium) {
                        $q->where('is_premium', $isPremium);
                    })
                    ->first();
                if (!$quiz) {
                    $quiz = Quiz::create([
                        'chapter_id' => $chapter->id,
                        'grade_id' => $chapter->grade_id,
                        'subject_id' => $chapter->subject_id,
                        'title' => ($isPremium ? 'AI Premium Quiz: ' : 'AI Quiz: ') . $chapter->title,
                        'description' => 'Chapter quiz generated automatically by AI.',
                        'is_premium' => $isPremium,
                    ]);
                } else {
                    $quiz->questions()->delete();
                }
                return app(AdminLearningController::class)->generateQuizQuestions($request, $quiz->id);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No AI-generated notes found for this chapter. Please generate chapter notes first before creating a quiz.',
            ], 422);
        }

        // Check if quiz already exists and force/overwrite/regenerate is not requested
        $existingQuiz = Quiz::where('chapter_id', $chapter->id)
            ->where(function ($q) {
                $q->where('is_premium', false)->orWhereNull('is_premium');
            })
            ->first();

        if ($existingQuiz && !$shouldRegenerate) {
            return response()->json([
                'status' => 'success',
                'message' => 'Quiz already exists for this chapter. Pass force=true, overwrite=true, or regenerate=true to replace with a new AI quiz.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'quiz_id' => $existingQuiz->id,
                    'title' => $existingQuiz->title,
                    'already_exists' => true,
                ]
            ]);
        }

        // Clear any previous failure lock for this chapter
        Cache::forget("quiz_failed_chapter_{$chapter->id}");

        // Sync execution support (e.g. for testing)
        if ($request->boolean('sync')) {
            try {
                $job = new GenerateChapterQuizJob($chapter);
                $job->handle(app(\App\Services\GeminiService::class));

                $quiz = Quiz::where('chapter_id', $chapter->id)
                    ->where(function ($q) {
                        $q->where('is_premium', false)->orWhereNull('is_premium');
                    })
                    ->with('questions')
                    ->first();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Quiz generated synchronously.',
                    'data' => $quiz,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed generating quiz: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Dispatch background job
        GenerateChapterQuizJob::dispatch($chapter);

        // Record audit trail
        AuditLogService::log('quiz.ai_generated', $chapter, null, [
            'chapter_id' => $chapter->id,
            'title' => $chapter->title,
            'is_premium' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Gemini AI is generating quiz questions in the background.',
            'data' => [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'status' => 'processing',
            ]
        ]);
    }

    /**
     * Get the quiz and its questions for a specific chapter (standard or premium).
     * Safely triggers on-demand AI quiz generation with cache stampede protection and smart source fallback.
     *
     * @param int|string|Request $idOrRequest
     * @param int|string|null $possibleId
     * @param Request|null $explicitRequest
     * @return JsonResponse
     */
    public function getQuiz($idOrRequest, $possibleId = null, ?Request $explicitRequest = null): JsonResponse
    {
        if ($idOrRequest instanceof Request) {
            $request = $idOrRequest;
            $id = $possibleId;
        } else {
            $request = $explicitRequest ?? request();
            $id = $idOrRequest;
        }

        $chapter = Chapter::with(['textbooks'])->find($id);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        $isPremium = strtolower((string) $request->query('type', 'standard')) === 'premium';
        $userResourceIds = $request->user() ? $request->user()->getUnlockedResourceIdsForChapter($chapter->id) : [];
        $sortedResourceIds = array_values(array_unique(array_map('intval', $userResourceIds)));
        sort($sortedResourceIds);
        $signature = !empty($sortedResourceIds) ? ('res_' . implode('_', $sortedResourceIds)) : null;

        if ($isPremium) {
            if (empty($sortedResourceIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase a chapter resource to access premium quizzes',
                ], 403);
            }

            // 1. Check if premium quiz already exists in database with chapter_id and resource_signature
            $quiz = Quiz::where('chapter_id', $id)
                ->where('is_premium', true)
                ->where('resource_signature', $signature)
                ->with('questions')
                ->first();

            if ($quiz) {
                $quizArray = $quiz->toArray();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Chapter premium quiz retrieved successfully',
                    'data' => array_merge($quizArray, [
                        'chapter_id' => $chapter->id,
                        'quiz_status' => 'ready',
                        'is_premium' => true,
                        'resource_signature' => $signature,
                        'quiz' => $quizArray,
                    ]),
                ], 200);
            }

            // 2. Check for recent failure lock
            $failedQuizKey = "premium_quiz_failed_{$chapter->id}_{$signature}";
            if (Cache::has($failedQuizKey)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Premium quiz generation previously failed for this chapter. Please contact support or notify admin.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'quiz_status' => 'not_available',
                        'is_premium' => true,
                        'resource_signature' => $signature,
                    ]
                ], 200);
            }

            // 3. Stampede lock check
            $lockKey = "generating_premium_quiz_{$chapter->id}_{$signature}";
            if (Cache::has($lockKey)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Premium quiz is currently being generated by AI.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'quiz_status' => 'generating',
                        'is_premium' => true,
                        'resource_signature' => $signature,
                        'retry_after_seconds' => 10,
                    ]
                ], 200);
            }

            // 4. Check if sources exist (variant notes or physical PDFs)
            $variant = ChapterPremiumVariant::where('chapter_id', $chapter->id)
                ->where('resource_signature', $signature)
                ->first();
            $hasVariantNotes = $variant && strlen(trim((string)$variant->ai_premium_notes)) >= 200;
            $hasPdfSource = (bool) $chapter->resolvePdfPath();

            if (!$hasVariantNotes && !$hasPdfSource) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No premium notes or supplementary resource PDF available to generate a premium quiz.',
                    'data' => [
                        'chapter_id' => $chapter->id,
                        'quiz_status' => 'not_available',
                        'is_premium' => true,
                        'resource_signature' => $signature,
                    ]
                ], 200);
            }

            // 5. Acquire lock & dispatch job
            Cache::put($lockKey, true, now()->addMinutes(3));
            GenerateCumulativePremiumQuizJob::dispatch($chapter, $sortedResourceIds);

            return response()->json([
                'status' => 'success',
                'message' => 'AI premium quiz generation started. Check back shortly.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'quiz_status' => 'generating',
                    'is_premium' => true,
                    'resource_signature' => $signature,
                    'retry_after_seconds' => $hasVariantNotes ? 8 : 15,
                ]
            ], 200);
        }

        // Standard Quiz (Existing flow with is_premium = false query)
        $quiz = Quiz::where('chapter_id', $id)
            ->where(function ($q) {
                $q->where('is_premium', false)->orWhereNull('is_premium');
            })
            ->with('questions')
            ->first();

        if ($quiz) {
            $quizArray = $quiz->toArray();
            return response()->json([
                'status' => 'success',
                'message' => 'Chapter quiz retrieved successfully',
                'data' => array_merge($quizArray, [
                    'chapter_id' => $chapter->id,
                    'quiz_status' => 'ready',
                    'is_premium' => false,
                    'quiz' => $quizArray,
                ]),
            ], 200);
        }

        // 2. Check for recent failure lock
        $failedQuizKey = "quiz_failed_chapter_{$chapter->id}";
        if (Cache::has($failedQuizKey)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Quiz generation previously failed for this chapter. Please contact support or notify admin.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'quiz_status' => 'not_available',
                    'is_premium' => false,
                ]
            ], 200);
        }

        // 3. Check if source exists (AI notes or physical textbook PDF)
        $hasSource = !empty(trim((string) $chapter->ai_generated_notes)) || (bool) $chapter->resolvePdfPath();

        if (!$hasSource) {
            return response()->json([
                'status' => 'success',
                'message' => 'No notes or textbook PDF available to generate a quiz.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'quiz_status' => 'not_available',
                    'is_premium' => false,
                ]
            ], 200);
        }

        // 4. Stampede lock check
        $lockKey = "generating_quiz_chapter_{$chapter->id}";
        if (Cache::has($lockKey)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Quiz is currently being generated by AI.',
                'data' => [
                    'chapter_id' => $chapter->id,
                    'quiz_status' => 'generating',
                    'is_premium' => false,
                    'retry_after_seconds' => 10,
                ]
            ], 200);
        }

        // 5. Acquire lock & dispatch job
        Cache::put($lockKey, true, now()->addMinutes(3));
        GenerateChapterQuizJob::dispatch($chapter);

        return response()->json([
            'status' => 'success',
            'message' => 'AI quiz generation started. Check back shortly.',
            'data' => [
                'chapter_id' => $chapter->id,
                'quiz_status' => 'generating',
                'is_premium' => false,
                'retry_after_seconds' => !empty($chapter->ai_generated_notes) ? 8 : 15,
            ]
        ], 200);
    }

    /**
     * Get chapter content (notes) using natural parameters: grade, subject, chapter number.
     *
     * @param string|int|Request $gradeOrRequest
     * @param string|int|null $subjectOrGrade
     * @param int|string|null $chapterNumberOrSubject
     * @param int|string|null $possibleChapterNumber
     * @return JsonResponse
     */
    public function getContentByLookup($gradeOrRequest, $subjectOrGrade = null, $chapterNumberOrSubject = null, $possibleChapterNumber = null): JsonResponse
    {
        if ($gradeOrRequest instanceof Request) {
            $request = $gradeOrRequest;
            $grade = $subjectOrGrade;
            $subject = $chapterNumberOrSubject;
            $chapterNumber = $possibleChapterNumber;
        } else {
            $request = request();
            $grade = $gradeOrRequest;
            $subject = $subjectOrGrade;
            $chapterNumber = $chapterNumberOrSubject;
        }

        $resolved = $this->resolveChapterByNaturalKeys($grade, $subject, $chapterNumber);

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        return $this->getContent($request, $resolved->id);
    }

    /**
     * Get chapter quiz using natural parameters: grade, subject, chapter number.
     *
     * @param string|int|Request $gradeOrRequest
     * @param string|int|null $subjectOrGrade
     * @param int|string|null $chapterNumberOrSubject
     * @param int|string|null $possibleChapterNumber
     * @return JsonResponse
     */
    public function getQuizByLookup($gradeOrRequest, $subjectOrGrade = null, $chapterNumberOrSubject = null, $possibleChapterNumber = null): JsonResponse
    {
        if ($gradeOrRequest instanceof Request) {
            $request = $gradeOrRequest;
            $grade = $subjectOrGrade;
            $subject = $chapterNumberOrSubject;
            $chapterNumber = $possibleChapterNumber;
        } else {
            $request = request();
            $grade = $gradeOrRequest;
            $subject = $subjectOrGrade;
            $chapterNumber = $chapterNumberOrSubject;
        }

        $resolved = $this->resolveChapterByNaturalKeys($grade, $subject, $chapterNumber);

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        return $this->getQuiz($request, $resolved->id);
    }

    /**
     * Download the physical resource file for paid students.
     *
     * @param Request $request
     * @param int|string $chapterId
     * @param int|string $resourceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadResource(Request $request, $chapterId, $resourceId): \Symfony\Component\HttpFoundation\Response
    {
        $chapter = Chapter::find($chapterId);
        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        $resource = Resource::where('chapter_id', $chapterId)->find($resourceId);
        if (!$resource) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found for this chapter',
            ], 404);
        }

        $user = $request->user();
        $isAdmin = $user && (!empty($user->is_admin) || in_array($user->role, ['super_admin', 'academic_editor', 'admin']));
        $isFree = empty($resource->price) || (float)$resource->price <= 0;
        $userResourceIds = $user ? $user->getUnlockedResourceIdsForChapter($chapterId) : [];
        $hasPaid = in_array((int)$resourceId, array_map('intval', $userResourceIds), true);

        if (!$isAdmin && !$isFree && !$hasPaid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment required to download this resource',
            ], 403);
        }

        $physicalPath = $chapter->resolveResourcePdfPath($resource->id);

        if (!$physicalPath || !file_exists($physicalPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Physical resource file not found on server.',
            ], 404);
        }

        $downloadName = Str::slug($chapter->title . '-' . $resource->title) . '.' . pathinfo($physicalPath, PATHINFO_EXTENSION);

        return response()->download($physicalPath, $downloadName);
    }

    /**
     * Ask a question or chat with the AI tutor for a specific chapter.
     *
     * @param Request $request
     * @param int|string $id
     * @param GeminiService $gemini
     * @return JsonResponse
     */
    public function chat(Request $request, $id, GeminiService $gemini): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:600',
            'history' => 'nullable|array|max:10',
        ]);

        $chapter = Chapter::with(['grade', 'subject'])->find($id);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        if ($this->isPromptInjection((string) $request->message)) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => "I am your Asteghi AI tutor for {$chapter->title}. I can only assist with questions directly related to this chapter's study notes and textbook materials. Let's focus on your studies!",
                    'chapter_id' => $chapter->id
                ]
            ], 200);
        }

        try {
            $reply = $gemini->chatWithChapter(
                $chapter,
                $request->input('message'),
                $request->input('history', [])
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => $reply,
                    'chapter_id' => $chapter->id,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI Tutor is currently unavailable: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Chat with AI tutor using natural parameters: grade, subject, chapter number.
     *
     * @param Request $request
     * @param string|int $grade
     * @param string|int $subject
     * @param int|string $chapterNumber
     * @param GeminiService $gemini
     * @return JsonResponse
     */
    public function chatByLookup(Request $request, $grade, $subject, $chapterNumber, GeminiService $gemini): JsonResponse
    {
        $resolved = $this->resolveChapterByNaturalKeys($grade, $subject, $chapterNumber);

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $chapter = $resolved;

        if ($this->isPromptInjection((string) $request->message)) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => "I am your Asteghi AI tutor for {$chapter->title}. I can only assist with questions directly related to this chapter's study notes and textbook materials. Let's focus on your studies!",
                    'chapter_id' => $chapter->id
                ]
            ], 200);
        }

        return $this->chat($request, $resolved->id, $gemini);
    }

    /**
     * Detect potential prompt injection or jailbreak attempts.
     *
     * @param string $message
     * @return bool
     */
    protected function isPromptInjection(string $message): bool
    {
        $patterns = [
            '/ignore\s+(all\s+)?(previous|above|prior)\s+(instructions|prompts|rules)/i',
            '/disregard\s+(all\s+)?(previous|above|prior)\s+(instructions|prompts|rules)/i',
            '/reveal\s+(your\s+)?(system\s+)?(prompt|instructions|rules|identity)/i',
            '/what\s+(are\s+)?(your\s+)?(system\s+)?instructions/i',
            '/you\s+are\s+now\s+(an?\s+)?(unrestricted|dan|jailbreak|developer|hacker)/i',
            '/act\s+as\s+(an?\s+)?(unrestricted|evil|dan|new\s+ai|different\s+ai)/i',
            '/bypass\s+(safety|rules|filters|limitations)/i',
            '/output\s+(initial|system)\s+prompt/i',
            '/developer\s+mode/i',
            '/repeat\s+the\s+words\s+above/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper method to resolve a Chapter model instance by natural keys (grade, subject, chapter_number).
     *
     * @param string|int $gradeParam
     * @param string|int $subjectParam
     * @param int|string $chapterNumber
     * @return Chapter|JsonResponse
     */
    protected function resolveChapterByNaturalKeys($gradeParam, $subjectParam, $chapterNumber): Chapter|JsonResponse
    {
        // 1. Resolve Grade
        $grade = null;
        if (is_numeric($gradeParam)) {
            $grade = Grade::select(['id', 'name'])
                ->where('id', $gradeParam)
                ->orWhere('name', 'like', "%{$gradeParam}%")
                ->first();
        } else {
            $cleanGrade = str_replace('-', ' ', (string) $gradeParam);
            $grade = Grade::select(['id', 'name'])
                ->where('name', $gradeParam)
                ->orWhere('name', 'like', "%{$gradeParam}%")
                ->orWhere('name', 'like', "%{$cleanGrade}%")
                ->first();
        }

        if (!$grade) {
            return response()->json([
                'status' => 'error',
                'message' => "Grade not found for: {$gradeParam}",
            ], 404);
        }

        // 2. Resolve Subject
        $cleanSubject = str_replace('-', ' ', (string) $subjectParam);
        $subject = Subject::select(['id', 'grade_id', 'title'])
            ->where('grade_id', $grade->id)
            ->where(function ($q) use ($subjectParam, $cleanSubject) {
                if (is_numeric($subjectParam)) {
                    $q->where('id', $subjectParam);
                }
                $q->orWhere('title', 'like', $subjectParam)
                  ->orWhere('title', 'like', "%{$subjectParam}%")
                  ->orWhere('title', 'like', "%{$cleanSubject}%");
            })
            ->first();

        if (!$subject) {
            $subject = Subject::select(['id', 'grade_id', 'title'])
                ->where(function ($q) use ($subjectParam, $cleanSubject) {
                    if (is_numeric($subjectParam)) {
                        $q->where('id', $subjectParam);
                    }
                    $q->orWhere('title', 'like', $subjectParam)
                      ->orWhere('title', 'like', "%{$subjectParam}%")
                      ->orWhere('title', 'like', "%{$cleanSubject}%");
                })->first();
        }

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => "Subject not found for: {$subjectParam}",
            ], 404);
        }

        // 3. Resolve Chapter
        $chapter = Chapter::select(['id', 'grade_id', 'subject_id', 'chapter_number', 'title', 'pdf_path', 'ai_generated_notes', 'notes_generated_at', 'description'])
            ->where('grade_id', $grade->id)
            ->where('subject_id', $subject->id)
            ->where('chapter_number', $chapterNumber)
            ->first();

        if (!$chapter) {
            $chapter = Chapter::select(['id', 'grade_id', 'subject_id', 'chapter_number', 'title', 'pdf_path', 'ai_generated_notes', 'notes_generated_at', 'description'])
                ->where('subject_id', $subject->id)
                ->where('chapter_number', $chapterNumber)
                ->first();
        }

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => "Chapter number {$chapterNumber} not found for Grade {$grade->name} and Subject {$subject->title}",
            ], 404);
        }

        return $chapter;
    }
}
