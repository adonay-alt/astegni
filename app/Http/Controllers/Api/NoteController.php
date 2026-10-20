<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    /**
     * Display a listing of notes with optional filtering by grade_id, subject_id, or chapter_id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Note::with(['grade', 'subject', 'chapter'])->where('is_active', 1);

        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('chapter_id')) {
            $query->where('chapter_id', $request->chapter_id);
        }
        if ($request->has('is_ai_generated')) {
            $query->where('is_ai_generated', filter_var($request->is_ai_generated, FILTER_VALIDATE_BOOLEAN));
        }

        // Order with Teacher notes first (0), AI notes second (1)
        $notes = $query->orderBy('is_ai_generated', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Notes retrieved successfully',
            'data' => $notes,
        ]);
    }

    /**
     * Display the specified note with its chapter.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $note = Note::with(['grade', 'subject', 'chapter'])->find($id);

        if (!$note) {
            return response()->json([
                'status' => 'error',
                'message' => 'Note not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Note retrieved successfully',
            'data' => $note,
        ]);
    }

    /**
     * Display active notes belonging to a specific chapter for students (Two-Tab UI: Teacher vs AI).
     *
     * @param int|string $chapterId
     * @return JsonResponse
     */
    public function getByChapter($chapterId): JsonResponse
    {
        $chapterId = $chapterId 
            ?? request()->route('chapterId') 
            ?? request()->route('chapter_id') 
            ?? request()->route('id');

        $chapter = Chapter::with(['grade', 'subject'])->find($chapterId);

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chapter not found',
            ], 404);
        }

        // 1. Fetch active notes only for this chapter (Teacher first, then AI)
        $notes = Note::with(['grade', 'subject', 'chapter'])
            ->where('chapter_id', $chapterId)
            ->where('is_active', 1)
            ->orderBy('is_ai_generated', 'asc')
            ->get();

        // 2. Separate into Teacher Note and AI Note
        $teacherNote = $notes->firstWhere('is_ai_generated', false);
        $aiNote = $notes->firstWhere('is_ai_generated', true);

        // AI notes are always visible to students; ensure any existing AI note is activated
        if (!$aiNote) {
            $existingAiNote = Note::with(['grade', 'subject', 'chapter'])
                ->where('chapter_id', $chapterId)
                ->where('is_ai_generated', true)
                ->first();
            if ($existingAiNote) {
                $existingAiNote->update(['is_active' => true]);
                $aiNote = $existingAiNote;
            }
        }

        // 3. Fallback for AI note: If $aiNote is null but $chapter->ai_generated_notes exists,
        // automatically create/sync the active AI note record in notes table so the student sees it immediately.
        if (!$aiNote && !empty($chapter->ai_generated_notes)) {
            $gradeId = $chapter->grade_id ?? ($chapter->subject ? $chapter->subject->grade_id : null);
            $aiNote = Note::updateOrCreate(
                [
                    'chapter_id' => $chapter->id,
                    'is_ai_generated' => true,
                ],
                [
                    'grade_id' => $gradeId,
                    'subject_id' => $chapter->subject_id,
                    'title' => "{$chapter->title} Notes",
                    'content' => $chapter->ai_generated_notes,
                    'is_active' => true,
                ]
            );
            $aiNote->load(['grade', 'subject', 'chapter']);
        }

        // Secondary fallback: If neither note exists, attempt resolution via ChapterController if available
        if (!$teacherNote && !$aiNote) {
            if (class_exists(ChapterController::class) && method_exists(ChapterController::class, 'resolveOrGenerateActiveNote')) {
                ChapterController::resolveOrGenerateActiveNote($chapter);
                $chapter->refresh();

                if (!empty($chapter->ai_generated_notes)) {
                    $gradeId = $chapter->grade_id ?? ($chapter->subject ? $chapter->subject->grade_id : null);
                    $aiNote = Note::updateOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'is_ai_generated' => true,
                        ],
                        [
                            'grade_id' => $gradeId,
                            'subject_id' => $chapter->subject_id,
                            'title' => "{$chapter->title} Notes",
                            'content' => $chapter->ai_generated_notes,
                            'is_active' => true,
                        ]
                    );
                    $aiNote->load(['grade', 'subject', 'chapter']);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'chapter' => [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'chapter_number' => $chapter->chapter_number,
                ],
                'teacher_note' => $teacherNote,
                'has_teacher_note' => !is_null($teacherNote),
                'ai_note' => $aiNote,
                'has_ai_note' => !is_null($aiNote),
            ]
        ]);
    }

    /**
     * Create a new note with relationship data integrity validation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required|integer|exists:chapters,id',
            'grade_id' => 'sometimes|integer|exists:grades,id',
            'subject_id' => 'sometimes|integer|exists:subjects,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_ai_generated' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chapter = Chapter::findOrFail($request->chapter_id);

        if ($request->has('grade_id') && (int)$request->grade_id !== (int)$chapter->grade_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'grade_id' => ['The selected grade does not match the specified chapter.'],
                ],
            ], 422);
        }

        if ($request->has('subject_id') && (int)$request->subject_id !== (int)$chapter->subject_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => [
                    'subject_id' => ['The selected subject does not match the specified chapter.'],
                ],
            ], 422);
        }

        $isAiGenerated = $request->boolean('is_ai_generated', false);

        $gradeId = $chapter->grade_id ?? ($chapter->subject ? $chapter->subject->grade_id : null);

        $note = Note::updateOrCreate(
            [
                'chapter_id' => $chapter->id,
                'is_ai_generated' => $isAiGenerated,
            ],
            [
                'grade_id' => $gradeId,
                'subject_id' => $chapter->subject_id,
                'title' => $request->title,
                'content' => $request->content,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Note saved successfully',
            'data' => $note,
        ], 201);
    }
}
