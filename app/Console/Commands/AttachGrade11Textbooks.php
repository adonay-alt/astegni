<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\Textbook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AttachGrade11Textbooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'textbooks:attach-grade11 {--dry-run : Perform matching analysis without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely connect uploaded Grade 11 textbook PDFs in storage to Grade 11 database chapters';

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
        'english' => 'english',
        'geography' => 'geography',
        'history' => 'history',
        'amharic' => 'አማርኛ ቋንቋ',
        'biology' => 'biology',
        'chemistry' => 'chemistry',
        'citizenship' => 'citizenship education',
        'mathematics' => 'mathematics',
        'maths' => 'mathematics',
        'math' => 'mathematics',
        'physics' => 'physics',
        'agriculture' => 'agriculture',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Grade 11 textbook PDF matching process...');

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            if (config('database.default') !== 'sqlite' && file_exists(database_path('database.sqlite'))) {
                $this->warn('Default database connection failed. Falling back to SQLite database.');
                config(['database.default' => 'sqlite']);
            }
        }

        $grade11 = Grade::where('name', 'like', '%11%')->first();
        if (!$grade11) {
            $this->error('Grade 11 record not found in database!');
            return Command::FAILURE;
        }

        $this->info("Found Grade 11 Record: ID={$grade11->id}, Name='{$grade11->name}'");

        $subjects = Subject::where('grade_id', $grade11->id)->get();
        if ($subjects->isEmpty()) {
            $this->error('No subjects found for Grade 11 in database!');
            return Command::FAILURE;
        }

        $relativeFolderPath = 'textbooks/grade11';
        $fullFolderPath = storage_path('app/public/' . $relativeFolderPath);

        if (!is_dir($fullFolderPath)) {
            $legacyFolderPath = storage_path('app/public/textbooks/grade 11');
            if (is_dir($legacyFolderPath)) {
                @rename($legacyFolderPath, $fullFolderPath);
            }
        }

        if (!is_dir($fullFolderPath)) {
            $fullFolderPath = public_path('storage/' . $relativeFolderPath);
        }

        // Local development fallback to Telegram Desktop directory if storage folder is not yet populated
        $searchFolders = [];
        if (is_dir($fullFolderPath)) {
            $searchFolders[] = $fullFolderPath;
        }
        $localTelegramDir = 'C:/Users/abush/Downloads/Telegram Desktop';
        if (is_dir($localTelegramDir)) {
            $searchFolders[] = $localTelegramDir;
        }

        if (empty($searchFolders)) {
            $this->error("Directory not found at: {$fullFolderPath}");
            return Command::FAILURE;
        }

        // Recursively find all PDFs
        $allDiscoveredPdfs = [];
        foreach ($searchFolders as $folder) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile() && strtolower($item->getExtension()) === 'pdf') {
                    $fullPathLower = strtolower($item->getPathname());
                    $filenameLower = strtolower($item->getFilename());

                    if (
                        str_contains($fullPathLower, 'grade11') ||
                        str_contains($fullPathLower, 'grade-11') ||
                        str_contains($fullPathLower, 'grade_11') ||
                        str_contains($filenameLower, 'grade11') ||
                        str_contains($filenameLower, 'grade-11') ||
                        str_contains($filenameLower, 'grade_11') ||
                        str_contains($fullPathLower, 'hpe') ||
                        str_contains($fullPathLower, 'it')
                    ) {
                        $filename = $item->getFilename();
                        if (!isset($allDiscoveredPdfs[$filename])) {
                            $allDiscoveredPdfs[$filename] = [
                                'filename' => $filename,
                                'full_path' => $item->getPathname(),
                            ];
                        }
                    }
                }
            }
        }

        $pdfFiles = array_values($allDiscoveredPdfs);
        $this->info("Scanned storage folder: Found " . count($pdfFiles) . " Grade 11 PDF files.");

        $unmatchedFiles = [];
        $chapterPdfsMap = [];

        foreach ($pdfFiles as $pdfInfo) {
            $pdf = $pdfInfo['filename'];
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

            if ($chNum === null || $chNum === 0) {
                $unmatchedFiles[] = [
                    'filename' => $pdf,
                    'reason' => $chNum === 0 ? "Frontmatter / Cover PDF (Chapter 0)" : "Chapter number could not be extracted",
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
                    'full_paths' => [],
                ];
            }
            $chapterPdfsMap[$mapKey]['pdfs'][] = $pdf;
            $chapterPdfsMap[$mapKey]['full_paths'][] = $pdfInfo['full_path'];
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
                    $fullPaths = $chapterPdfsMap[$mapKey]['full_paths'];
                    if (count($pdfs) === 1) {
                        $validMatches[] = [
                            'pdf' => $pdfs[0],
                            'subject' => $subj,
                            'chapter' => $c,
                            'full_path' => $fullPaths[0],
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
        $this->line("GRADE 11 MATCHING SUMMARY REPORT");
        $this->line("==========================================");
        $this->line("Total Grade 11 chapters in DB : {$totalChaptersInDb}");
        $this->line("Total PDFs scanned            : " . count($pdfFiles));
        $this->info("Successfully matched PDFs    : {$matchedCount}");
        $this->line("Missing PDFs (Chapters w/o)   : {$missingCount}");
        $this->line("Unmatched / Intro PDFs       : {$unmatchedCount}");
        $this->line("Duplicate PDF matches        : {$duplicateCount}");
        $this->line("==========================================");

        if ($unmatchedCount > 0) {
            $this->newLine();
            $this->warn("UNMATCHED / INTRO / FRONTMATTER FILES:");
            foreach ($unmatchedFiles as $u) {
                $this->warn("  - {$u['filename']} (Reason: {$u['reason']})");
            }
        }

        if ($missingCount > 0) {
            $this->newLine();
            $this->warn("CHAPTERS WITHOUT PDF:");
            foreach ($missingChapters as $m) {
                $this->warn("  - {$m['subject']} Chapter {$m['chapter_number']}: {$m['chapter_name']}");
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

        // Copy matched local PDFs into storage/app/public/textbooks/grade11 if not already there
        $targetDir = storage_path('app/public/' . $relativeFolderPath);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        foreach ($validMatches as $m) {
            $destFile = $targetDir . '/' . $m['pdf'];
            if (!file_exists($destFile) && file_exists($m['full_path'])) {
                @copy($m['full_path'], $destFile);
            }
        }

        // Perform database insertion idempotently inside a transaction
        $this->newLine();
        $this->info("Inserting/Updating database records inside transaction...");

        DB::transaction(function () use ($validMatches, $grade11) {
            foreach ($validMatches as $m) {
                $subj = $m['subject'];
                $chap = $m['chapter'];
                $relativePath = $m['relative_path'];
                $title = "Grade 11 {$subj->title} Chapter {$chap->chapter_number} Textbook";

                Textbook::updateOrCreate(
                    [
                        'chapter_id' => $chap->id,
                    ],
                    [
                        'grade_id' => $grade11->id,
                        'subject_id' => $subj->id,
                        'title' => $title,
                        'description' => "Official Grade 11 textbook for {$subj->title}, Chapter {$chap->chapter_number}: {$chap->title}",
                        'file_url' => $relativePath,
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

        if (str_contains($normalized, 'frontmatter') || str_contains($normalized, 'appendix')) {
            return [
                'original_filename' => $filename,
                'subject_token' => 'frontmatter',
                'chapter_number' => 0,
                'subject' => null,
            ];
        }

        $chapterNum = null;
        if (preg_match('/chapter[-_\s]*(\d+)/i', $normalized, $matches)) {
            $chapterNum = (int) $matches[1];
        } elseif (preg_match('/_(\d+)$/', $normalized, $matches)) {
            $chapterNum = (int) $matches[1];
        } elseif (preg_match('/-(\d+)$/', $normalized, $matches)) {
            $chapterNum = (int) $matches[1];
        }

        $matchedSubject = null;

        foreach ($subjects as $s) {
            $sTitleLower = strtolower($s->title);
            if (str_contains($normalized, 'agriculture') && $sTitleLower === 'agriculture') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'biology') && $sTitleLower === 'biology') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'chemistry') && $sTitleLower === 'chemistry') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'english') && $sTitleLower === 'english') { $matchedSubject = $s; break; }
            if ((str_contains($normalized, '_it_') || str_ends_with($normalized, '_it') || str_starts_with($normalized, 'it_')) && $sTitleLower === 'information technology') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'mathematics') && $sTitleLower === 'mathematics') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'physics') && $sTitleLower === 'physics') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'geography') && $sTitleLower === 'geography') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'economics') && $sTitleLower === 'economics') { $matchedSubject = $s; break; }
            if (str_contains($normalized, 'history') && $sTitleLower === 'history') { $matchedSubject = $s; break; }
            if ((str_contains($normalized, 'hpe') || str_contains($normalized, 'health')) && str_contains($sTitleLower, 'health')) { $matchedSubject = $s; break; }
        }

        return [
            'original_filename' => $filename,
            'subject_token' => $matchedSubject ? $matchedSubject->title : 'unknown',
            'chapter_number' => $chapterNum,
            'subject' => $matchedSubject,
        ];
    }
}
