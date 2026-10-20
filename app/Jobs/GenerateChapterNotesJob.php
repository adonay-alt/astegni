<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\Note;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GenerateChapterNotesJob implements ShouldQueue
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
        $lockKey = "generating_notes_chapter_{$this->chapter->id}";
        $isReleased = false;

        try {
            $pdfPath = $this->chapter->resolvePdfPath();

            if (!$pdfPath || !file_exists($pdfPath)) {
                Log::warning("PDF missing for chapter {$this->chapter->id}, skipping without retry.");
                Cache::put("notes_failed_chapter_{$this->chapter->id}", "Physical PDF file missing on server disk.", now()->addMinutes(10));
                $this->delete();
                return;
            }

            // Generate notes via Gemini API service
            $notes = $gemini->generateNotesFromPdf($pdfPath, $this->chapter->title);

            // Validate completeness before saving
            $isComplete = strlen($notes) >= 1500 && (
                str_contains($notes, 'Practice') || 
                str_contains($notes, 'Review') || 
                str_contains($notes, '6.')
            );
            if (!$isComplete) {
                throw new Exception("Notes were cut off prematurely before reaching Section 6. Retrying...");
            }

            // Update Chapter model
            $this->chapter->update([
                'ai_generated_notes' => $notes,
                'notes_generated_at' => now(),
            ]);

            // Synchronize with the notes table
            Note::updateOrCreate(
                [
                    'chapter_id' => $this->chapter->id,
                    'title' => 'AI Generated Notes: ' . $this->chapter->title,
                ],
                [
                    'grade_id' => $this->chapter->grade_id,
                    'subject_id' => $this->chapter->subject_id,
                    'content' => $notes,
                    'is_ai_generated' => true,
                    'is_active' => true,
                ]
            );

            sleep(3);
            Log::info("Notes generated successfully for Chapter ID: {$this->chapter->id}");
        } catch (Exception $e) {
            $isTransient = str_contains($e->getMessage(), '429') 
                || str_contains($e->getMessage(), '503') 
                || str_contains($e->getMessage(), 'high demand') 
                || str_contains($e->getMessage(), 'Quota exceeded')
                || str_contains($e->getMessage(), '504')
                || str_contains($e->getMessage(), '502')
                || str_contains($e->getMessage(), 'cURL error 28')
                || str_contains($e->getMessage(), 'cut off prematurely');

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
            Cache::put("notes_failed_chapter_{$this->chapter->id}", $e->getMessage(), now()->addMinutes(10));
            $this->fail($e);
        } finally {
            if (!$isReleased) {
                Cache::forget($lockKey);
            }
        }
    }
}
