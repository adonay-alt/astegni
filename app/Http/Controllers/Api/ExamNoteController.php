<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamNoteController extends Controller
{
    /**
     * Display a listing of exam short notes with optional filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Note::with(['grade', 'subject', 'chapter']);

        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->query('grade_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('chapter_id')) {
            $query->where('chapter_id', $request->query('chapter_id'));
        }

        if ($request->filled('q') || $request->filled('search')) {
            $searchTerm = strtolower(trim($request->query('q') ?? $request->query('search')));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(content) LIKE ?', ['%' . $searchTerm . '%']);
            });
        }

        $notes = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Exam notes retrieved successfully',
            'data' => $notes,
        ], 200);
    }

    /**
     * Display a single exam short note.
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
                'message' => 'Exam note not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Exam note retrieved successfully',
            'data' => $note,
        ], 200);
    }
}
