<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\Textbook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachGrade10Textbooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'textbooks:attach-grade10 {--dry-run : Perform matching analysis without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely connect uploaded Grade 10 textbook PDFs in storage to Grade 10 database chapters';

    /**
     * Subject alias mapping from filename tokens (lowercase) to database subject titles (lowercase).
     *
     * @var array<string, string>
     */
    protected array $aliasMap = [
        'it' => 'information technology',
        'information technology' => 'information technology',
        'hpe' => 'health and physical education',
        'health and physical education' => 'health and physical education',
        'economics' => 'economics',
        'english' => 'english for ethiopia',
        'english for ethiopia' => 'english for ethiopia',
        'geography' => 'geography',
        'history' => 'history',
        'amharic' => 'አማርኛ ቋንቋ',
        'አማርኛ ቋንቋ' => 'አማርኛ ቋንቋ',
        'biology' => 'biology',
        'chemistry' => 'chemistry',
        'citizenship' => 'citizenship education',
        'citizenship education' => 'citizenship education',
        'mathematics' => 'mathematics',
        'maths' => 'mathematics',
        'math' => 'mathematics',
        'physics' => 'physics',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Grade 10 textbook PDF matching process...');

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            if (config('database.default') !== 'sqlite' && file_exists(database_path('database.sqlite'))) {
                $this->warn('Default database connection failed. Falling back to SQLite database.');
                config(['database.default' => 'sqlite']);
            }
        }

        $grade10 = Grade::where('name', 'like', '%10%')->first();
        if (!$grade10) {
            $this->error('Grade 10 record not found in database!');
            return Command::FAILURE;
        }

        $this->info("Found Grade 10 Record: ID={$grade10->id}, Name='{$grade10->name}'");

        $subjects = Subject::where('grade_id', $grade10->id)->get();
        if ($subjects->isEmpty()) {
            $this->error('No subjects found for Grade 10 in database!');
            return Command::FAILURE;
        }

        $relativeFolderPath = 'textbooks/grade10';
        $fullFolderPath = storage_path('app/public/' . $relativeFolderPath);

        if (!is_dir($fullFolderPath)) {
            $legacyFolderPath = storage_path('app/public/textbooks/grade 10');
            if (is_dir($legacyFolderPath)) {
                @rename($legacyFolderPath, $fullFolderPath);
            }
        }

        if (!is_dir($fullFolderPath)) {
            // Check public storage fallback if storage_path differs
            $fullFolderPath = public_path('storage/' . $relativeFolderPath);
        }

        if (!is_dir($fullFolderPath)) {
            $this->error("Directory not found at: {$fullFolderPath}");
            return Command::FAILURE;
        }

        $allFiles = array_diff(scandir($fullFolderPath), ['.', '..']);
        $pdfFiles = [];
        foreach ($allFiles as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
                $pdfFiles[] = $file;
            }
        }
        sort($pdfFiles);

        $this->info("Scanned storage folder: Found " . count($pdfFiles) . " PDF files.");

        // Parse and match PDFs
        $matchedMap = [];
        $unmatchedFiles = [];
        $chapterPdfsMap = [];

        foreach ($pdfFiles as $pdf) {
            $parsed = $this->parseFilename($pdf, $subjects);
            $subj = $parsed['subject'];
            $chNum = $parsed['chapter_number'];

            if (!$subj) {
                $unmatchedFiles[] = [
                    'filename' => $pdf,
                    'reason' => "Subject could not be matched ('{$parsed['subject_token']}')",
                ];
                continue;
            }

            if ($chNum === null) {
                $unmatchedFiles[] = [
                    'filename' => $pdf,
                    'reason' => "Chapter number could not be extracted",
                ];
                continue;
            }

            $dbChapter = Chapter::where('subject_id', $subj->id)
                ->where('chapter_number', $chNum)
                ->first();

            if (!$dbChapter) {
                $unmatchedFiles[] = [
                    'filename' => $pdf,
                    'reason' => "No DB chapter found for Subject '{$subj->title}' Chapter {$chNum}",
                ];
                continue;
            }

            $mapKey = $subj->id . '_' . $chNum;
            if (!isset($chapterPdfsMap[$mapKey])) {
                $chapterPdfsMap[$mapKey] = [
                    'subject' => $subj,
                    'chapter' => $dbChapter,
                    'pdfs' => [],
                ];
            }
            $chapterPdfsMap[$mapKey]['pdfs'][] = $pdf;
        }

        // Detect duplicates and confirm matches
        $validMatches = [];
        $duplicateMatches = [];
        $missingChapters = [];

        foreach ($subjects as $subj) {
            $chapters = Chapter::where('subject_id', $subj->id)->orderBy('chapter_number')->get();
            foreach ($chapters as $c) {
                $mapKey = $subj->id . '_' . $c->chapter_number;
                if (isset($chapterPdfsMap[$mapKey])) {
                    $pdfs = $chapterPdfsMap[$mapKey]['pdfs'];
                    if (count($pdfs) === 1) {
                        $validMatches[] = [
                            'pdf' => $pdfs[0],
                            'subject' => $subj,
                            'chapter' => $c,
                            'relative_path' => $relativeFolderPath . '/' . $pdfs[0],
                        ];
                    } else {
                        $duplicateMatches[] = [
                            'subject' => $subj->title,
                            'chapter_number' => $c->chapter_number,
                            'pdfs' => $pdfs,
                        ];
                    }
                } else {
                    $missingChapters[] = [
                        'subject' => $subj->title,
                        'chapter_number' => $c->chapter_number,
                        'chapter_name' => $c->title,
                    ];
                }
            }
        }

        $totalChaptersInDb = Chapter::whereIn('subject_id', $subjects->pluck('id'))->count();
        $matchedCount = count($validMatches);
        $missingCount = count($missingChapters);
        $unmatchedCount = count($unmatchedFiles);
        $duplicateCount = count($duplicateMatches);

        $this->newLine();
        $this->line("==========================================");
        $this->line("MATCHING SUMMARY REPORT");
        $this->line("==========================================");
        $this->line("Total Grade 10 chapters in DB : {$totalChaptersInDb}");
        $this->line("Total PDFs found in storage  : " . count($pdfFiles));
        $this->info("Successfully matched PDFs    : {$matchedCount}");
        $this->line("Missing PDFs (Chapters w/o)   : {$missingCount}");
        $this->line("Unmatched PDFs               : {$unmatchedCount}");
        $this->line("Duplicate PDF matches        : {$duplicateCount}");
        $this->line("==========================================");

        if ($unmatchedCount > 0) {
            $this->newLine();
            $this->warn("UNMATCHED / NEEDS REVIEW:");
            foreach ($unmatchedFiles as $u) {
                $this->warn("  - {$u['filename']} (Reason: {$u['reason']})");
            }
        }

        if ($duplicateCount > 0) {
            $this->newLine();
            $this->warn("DUPLICATE MATCHES:");
            foreach ($duplicateMatches as $d) {
                $this->warn("  - Subject: {$d['subject']}, Ch {$d['chapter_number']} => PDFs: " . implode(', ', $d['pdfs']));
            }
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run enabled. Database was NOT updated.");
            return Command::SUCCESS;
        }

        // Perform database insertion idempotently inside a transaction
        $this->newLine();
        $this->info("Inserting/Updating database records inside transaction...");

        DB::transaction(function () use ($validMatches, $grade10) {
            foreach ($validMatches as $m) {
                $subj = $m['subject'];
                $chap = $m['chapter'];
                $relativePath = $m['relative_path'];
                $title = "Grade 10 {$subj->title} Chapter {$chap->chapter_number} Textbook";

                Textbook::updateOrCreate(
                    [
                        'chapter_id' => $chap->id,
                        'file_url' => $relativePath,
                    ],
                    [
                        'grade_id' => $grade10->id,
                        'subject_id' => $subj->id,
                        'title' => $title,
                        'description' => "Official Grade 10 textbook for {$subj->title}, Chapter {$chap->chapter_number}: {$chap->title}",
                    ]
                );
            }
        });

        $this->info("Successfully processed {$matchedCount} textbook PDF attachments in database!");
        return Command::SUCCESS;
    }

    /**
     * Parse filename to extract normalized subject token and chapter number.
     *
     * @param string $filename
     * @param \Illuminate\Support\Collection $subjects
     * @return array
     */
    protected function parseFilename(string $filename, $subjects): array
    {
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        $normalized = strtolower($nameWithoutExt);
        $normalized = str_replace(['_', '-'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        // Remove leading 'grade 10' or 'grade10'
        $normalized = preg_replace('/^grade\s*10\s*/i', '', $normalized);

        $chapterNum = null;
        if (preg_match('/(?:chapter|chap|ch)\s*(\d+)/i', $normalized, $matches)) {
            $chapterNum = (int) $matches[1];
            $subjectToken = trim(preg_replace('/(?:chapter|chap|ch)\s*\d+/i', '', $normalized));
        } elseif (preg_match('/(\d+)$/', $normalized, $matches)) {
            $chapterNum = (int) $matches[1];
            $subjectToken = trim(preg_replace('/\d+$/', '', $normalized));
        } else {
            $subjectToken = $normalized;
        }

        $subjectToken = trim($subjectToken);

        $matchedSubject = null;
        if (isset($this->aliasMap[$subjectToken])) {
            $targetTitleLower = $this->aliasMap[$subjectToken];
            foreach ($subjects as $s) {
                if (strtolower(trim($s->title)) === $targetTitleLower) {
                    $matchedSubject = $s;
                    break;
                }
            }
        } else {
            foreach ($subjects as $s) {
                $dbTitleLower = strtolower(trim($s->title));
                if ($subjectToken === $dbTitleLower || str_contains($subjectToken, $dbTitleLower) || str_contains($dbTitleLower, $subjectToken)) {
                    $matchedSubject = $s;
                    break;
                }
            }
        }

        return [
            'original_filename' => $filename,
            'subject_token' => $subjectToken,
            'chapter_number' => $chapterNum,
            'subject' => $matchedSubject,
        ];
    }
}
