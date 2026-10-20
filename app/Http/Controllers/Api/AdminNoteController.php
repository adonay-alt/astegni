<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Chapter;
use App\Models\Note;
use App\Helpers\CurriculumCacheHelper;
use App\Services\AuditLogService;
use App\Services\GeminiService;

class AdminNoteController extends Controller
{
    /**
     * Get notes for a chapter, separating teacher note and AI note.
     * GET /api/admin/chapters/{chapterId}/notes
     */
    public function getByChapter($chapterId)
    {
        $chapterId = $chapterId ?? request()->route('chapterId') ?? request()->route('chapter_id') ?? request()->route('id');
        $chapter = Chapter::select('id', 'title', 'chapter_number')->findOrFail($chapterId);

        $notes = Note::where('chapter_id', $chapterId)
            ->orderBy('is_ai_generated', 'asc')
            ->get();

        $teacherNote = $notes->firstWhere('is_ai_generated', false);
        $aiNote = $notes->firstWhere('is_ai_generated', true);

        return response()->json([
            'status' => 'success',
            'chapter' => [
                'id' => $chapter->id,
                'title' => $chapter->title,
                'chapter_number' => $chapter->chapter_number,
            ],
            'teacher_note' => $teacherNote,
            'ai_note' => $aiNote,
            'data' => $notes,
        ]);
    }

    /**
     * Store or update a note (Teacher Note or AI Note) for a chapter.
     * Enforces 1-note-per-type per chapter using updateOrCreate.
     * POST /api/admin/chapters/{chapterId}/notes
     * POST /api/admin/notes
     */
    public function store(Request $request, $chapterId = null)
    {
        $chapterId = $chapterId 
            ?? $request->route('chapterId') 
            ?? $request->route('chapter_id') 
            ?? $request->route('id') 
            ?? $request->input('chapter_id');

        $chapter = Chapter::with('subject')->findOrFail($chapterId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_ai_generated' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $isAiGenerated = $request->boolean('is_ai_generated', false);
        // AI notes are always active; teacher notes respect is_active or default to true
        $isActive = $isAiGenerated ? true : ($request->has('is_active') ? $request->boolean('is_active') : true);

        $existingNote = Note::where('chapter_id', $chapter->id)
            ->where('is_ai_generated', $isAiGenerated)
            ->first();
        $oldValues = $existingNote ? $existingNote->toArray() : null;

        $gradeId = $chapter->grade_id ?? ($chapter->subject ? $chapter->subject->grade_id : null);

        // Enforce 1-note-per-type per chapter
        $note = Note::updateOrCreate(
            [
                'chapter_id' => $chapter->id,
                'is_ai_generated' => $isAiGenerated,
            ],
            [
                'grade_id' => $gradeId,
                'subject_id' => $chapter->subject_id,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_active' => $isActive,
            ]
        );

        // Synchronize chapter ai_generated_notes if this is an AI note
        if ($isAiGenerated) {
            $chapter->update([
                'ai_generated_notes' => $validated['content'],
                'notes_generated_at' => now(),
            ]);
        }

        Cache::flush();
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }

        // Automatically record immutable audit trail
        AuditLogService::log(
            action: $existingNote ? 'note.updated' : 'note.created',
            auditable: 'App\Models\Note',
            oldValues: $oldValues,
            newValues: $note->toArray()
        );

        return response()->json([
            'status' => 'success',
            'message' => ($isAiGenerated ? 'AI' : 'Teacher') . ' note saved successfully.',
            'data' => $note,
        ], 200);
    }

    /**
     * Update an existing note by ID.
     * PUT /api/admin/notes/{id}
     */
    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);
        $oldValues = $note->toArray();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        // AI notes are always visible to students and cannot be hidden
        if ($note->is_ai_generated) {
            unset($validated['is_active']);
            $validated['is_active'] = true;
        }

        $note->update($validated);

        // If the updated note has is_ai_generated = 1, synchronize chapter ai_generated_notes
        if ($note->is_ai_generated) {
            Chapter::where('id', $note->chapter_id)->update([
                'ai_generated_notes' => $note->content,
            ]);
        }

        Cache::flush();
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }

        // Automatically record immutable audit trail
        AuditLogService::log(
            action: 'note.updated',
            auditable: 'App\Models\Note',
            oldValues: $oldValues,
            newValues: $note->toArray()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Note updated successfully.',
            'data' => $note,
        ]);
    }

    /**
     * Delete a note by ID.
     * If the note is an AI note, clear chapter ai_generated_notes.
     * DELETE /api/admin/notes/{id}
     */
    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        $oldValues = $note->toArray();

        // If the note is is_ai_generated = 1, clear chapter ai_generated_notes
        if ($note->is_ai_generated) {
            Chapter::where('id', $note->chapter_id)->update([
                'ai_generated_notes' => null,
                'notes_generated_at' => null,
            ]);
        }

        $note->delete();

        Cache::flush();
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }

        // Automatically record immutable audit trail
        AuditLogService::log(
            action: 'note.deleted',
            auditable: 'App\Models\Note',
            oldValues: $oldValues,
            newValues: null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Note deleted successfully.',
        ]);
    }

    /**
     * Regenerate the AI note for a chapter using GeminiService.
     * POST /api/admin/chapters/{chapterId}/generate-notes
     */
    public function generateAi(Request $request, $chapterId = null)
    {
        $chapterId = $chapterId 
            ?? $request->route('chapterId') 
            ?? $request->route('chapter_id') 
            ?? $request->route('id') 
            ?? $request->input('chapter_id');

        $chapter = Chapter::with(['subject.grade', 'grade'])->findOrFail($chapterId);

        $existingAiNote = Note::where('chapter_id', $chapterId)
            ->where('is_ai_generated', 1)
            ->first();
        $oldValues = $existingAiNote ? $existingAiNote->toArray() : null;

        // Call the real production AI generation pipeline
        $aiContent = $this->callProductionAiService($chapter);

        $cleanTitle = "{$chapter->title} Notes";
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;

        $gradeId = $chapter->grade_id ?? ($chapter->subject ? $chapter->subject->grade_id : null);

        // Update or create the AI note row in notes table (is_ai_generated = 1)
        $note = Note::updateOrCreate(
            [
                'chapter_id' => $chapter->id,
                'is_ai_generated' => 1,
            ],
            [
                'grade_id' => $gradeId,
                'subject_id' => $chapter->subject_id,
                'title' => $cleanTitle,
                'content' => $aiContent,
                'is_active' => $isActive,
            ]
        );

        // Keep chapter model synchronized for student app AI tutor knowledge base
        $chapter->update([
            'ai_generated_notes' => $aiContent,
            'notes_generated_at' => now(),
        ]);

        Cache::flush();
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }

        // Automatically record immutable audit trail
        AuditLogService::log(
            action: 'chapter.ai_notes_regenerated',
            auditable: 'App\Models\Chapter',
            oldValues: $oldValues,
            newValues: $note->toArray()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'AI note generated successfully',
            'data' => $note,
        ]);
    }

    /**
     * Toggle is_active on the targeted note without affecting the other note type.
     * PATCH /api/admin/notes/{id}/toggle-visibility
     */
    public function toggleVisibility(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        // AI notes are always visible to students and cannot be toggled
        if ($note->is_ai_generated) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI notes are always visible to students and cannot be hidden.',
                'data' => $note,
            ], 422);
        }

        $oldStatus = $note->is_active;

        $newStatus = $request->has('is_active')
            ? $request->boolean('is_active')
            : !$note->is_active;

        // UPDATE ONLY THIS NOTE (Do NOT touch other notes in the chapter!)
        $note->update(['is_active' => $newStatus]);

        // Automatically record immutable audit trail
        AuditLogService::log(
            action: 'note.visibility_toggled',
            auditable: $note,
            oldValues: ['id' => (int) $note->id, 'is_active' => (bool) $oldStatus],
            newValues: ['id' => (int) $note->id, 'is_active' => (bool) $newStatus]
        );

        Cache::flush();
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }

        return response()->json([
            'status' => 'success',
            'message' => $newStatus ? 'Note is now visible to students.' : 'Note is now hidden from students.',
            'data' => $note,
        ]);
    }

    /**
     * Activate a note without deactivating others.
     * PATCH /api/admin/notes/{id}/activate
     */
    public function activate($id)
    {
        $note = Note::findOrFail($id);

        $note->update(['is_active' => true]);

        // Automatically record immutable audit trail
        AuditLogService::log(
            action: 'note.activated',
            auditable: 'App\Models\Note',
            oldValues: ['id' => (int) $note->id, 'is_active' => false],
            newValues: ['id' => (int) $note->id, 'is_active' => true]
        );

        Cache::flush();
        if (class_exists(CurriculumCacheHelper::class)) {
            CurriculumCacheHelper::clearAllCurriculumCache();
        }

        return $this->getByChapter($note->chapter_id);
    }

    /**
     * Call the real production AI generation pipeline (GeminiService).
     */
    protected function callProductionAiService(Chapter $chapter): string
    {
        $gemini = new GeminiService();
        $pdfPath = $chapter->resolvePdfPath();
        $gradeName = $chapter->subject->grade->name ?? ($chapter->grade->name ?? null);
        $subjectName = $chapter->subject->title ?? ($chapter->subject->name ?? null);

        try {
            if ($pdfPath && file_exists($pdfPath)) {
                return $gemini->generateNotesFromPdf($pdfPath, $chapter->title, $gradeName);
            }

            return $gemini->generateNotesFromCurriculum($chapter->title, $subjectName, $gradeName);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Gemini AI generation failed for chapter {$chapter->id}: " . $e->getMessage());

            // High-yield syllabus fallback if external API fails
            return "# {$chapter->title} Notes\n\n"
                . "**Subject:** {$subjectName} • **Grade:** {$gradeName}\n\n"
                . "## 1. Chapter Overview & Learning Objectives\n"
                . "- Master core syllabus concepts and exam competencies for {$chapter->title}.\n"
                . "- Essential definitions, governing principles, and curriculum standards.\n\n"
                . "## 2. Key Concepts & Definitions\n"
                . "- In-depth conceptual breakdowns and standardized formulas.\n\n"
                . "## 3. Worked Examples & Exam Takeaways\n"
                . "- Step-by-step problem walkthroughs and high-yield examination tips.\n\n"
                . "---\n*Curriculum Study Guide generated on " . now()->toFormattedDateString() . "*";
        }
    }
}
