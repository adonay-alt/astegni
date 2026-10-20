<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateChapterQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 360;
    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public Chapter $chapter) {}

    /**
     * Execute the job.
     */
    public function handle(GeminiService $gemini): void
    {
        $lockKey = "generating_quiz_chapter_{$this->chapter->id}";
        $isReleased = false;

        try {
            // Smart Source Fallback: First try AI Notes (min 200 chars), fallback to Textbook PDF
            $notesText = trim((string) $this->chapter->ai_generated_notes);
            if (strlen($notesText) >= 200) {
                Log::info("Generating quiz from existing AI notes for Chapter ID: {$this->chapter->id}");
                $questionsData = $gemini->generateQuizFromText($notesText, $this->chapter->title);
            } else {
                if (strlen($notesText) > 0 && strlen($notesText) < 200) {
                    Log::warning("AI notes too short (" . strlen($notesText) . " chars) for Chapter ID: {$this->chapter->id}. Falling back to PDF.");
                }
                $pdfPath = $this->chapter->resolvePdfPath();
                if (!$pdfPath || !file_exists($pdfPath)) {
                    throw new Exception("No source available (notes too short and no PDF) for Chapter ID: {$this->chapter->id}");
                }
                Log::info("Generating quiz from textbook PDF for Chapter ID: {$this->chapter->id}");
                $questionsData = $gemini->generateQuizFromPdf($pdfPath, $this->chapter->title);
            }

            if (empty($questionsData)) {
                throw new Exception("No quiz questions were returned by Gemini API for Chapter ID: {$this->chapter->id}");
            }

            // Option mapping helper for correct_answer
            $mapOption = function ($raw) {
                $upper = strtoupper(trim((string)$raw));
                return match ($upper) {
                    'A', 'OPTION_A' => 'option_a',
                    'B', 'OPTION_B' => 'option_b',
                    'C', 'OPTION_C' => 'option_c',
                    'D', 'OPTION_D' => 'option_d',
                    default => 'option_a',
                };
            };

            $this->chapter->getConnection()->transaction(function () use ($questionsData, $mapOption) {
                // Update or create the quiz record for this chapter
                $quiz = Quiz::updateOrCreate(
                    [
                        'chapter_id' => $this->chapter->id,
                    ],
                    [
                        'grade_id' => $this->chapter->grade_id,
                        'subject_id' => $this->chapter->subject_id,
                        'title' => 'AI Quiz: ' . $this->chapter->title,
                        'description' => 'Comprehensive chapter quiz generated automatically by AI.',
                    ]
                );

                // Clear previous questions for this quiz to replace with new set
                $quiz->questions()->delete();

                // Insert generated questions
                foreach ($questionsData as $item) {
                    $options = $item['options'] ?? [];

                    QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question' => $item['question'] ?? 'Multiple choice question',
                        'option_a' => $options['A'] ?? ($item['option_a'] ?? 'Option A'),
                        'option_b' => $options['B'] ?? ($item['option_b'] ?? 'Option B'),
                        'option_c' => $options['C'] ?? ($item['option_c'] ?? 'Option C'),
                        'option_d' => $options['D'] ?? ($item['option_d'] ?? 'Option D'),
                        'correct_answer' => $mapOption($item['correct_answer'] ?? 'A'),
                        'explanation' => $item['explanation'] ?? null,
                    ]);
                }
            });

            sleep(3);

            Log::info("Quiz generated successfully for Chapter ID: {$this->chapter->id}");
        } catch (Exception $e) {
            $isTransient = str_contains($e->getMessage(), '429') 
                || str_contains($e->getMessage(), '503') 
                || str_contains($e->getMessage(), 'high demand') 
                || str_contains($e->getMessage(), 'Quota exceeded')
                || str_contains($e->getMessage(), '504')
                || str_contains($e->getMessage(), '502')
                || str_contains($e->getMessage(), 'cURL error 28');

            if ($isTransient) {
                Log::warning("Gemini transient error for Chapter {$this->chapter->id}: {$e->getMessage()}. Pausing 35s and retrying...");
                $isReleased = true;
                Cache::put($lockKey, true, now()->addSeconds(60));
                $this->release(35); // Wait 35s and retry cleanly
                return;
            }

            // For permanent failures:
            Log::error("Permanent failure for chapter {$this->chapter->id}: " . $e->getMessage());
            // Set failure cache for 10 mins so frontend knows not to loop indefinitely:
            Cache::put("quiz_failed_chapter_{$this->chapter->id}", $e->getMessage(), now()->addMinutes(10));
            $this->fail($e);
        } finally {
            if (!$isReleased) {
                Cache::forget($lockKey);
            }
        }
    }
}
