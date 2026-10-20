<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurriculumCacheService
{
    /**
     * Clear all cached curriculum data (grades, subjects, chapters, textbooks).
     */
    public static function clearAll(): void
    {
        try {
            // 1. Grade listings
            Cache::forget('api_grades_with_subjects');

            // 2. Clear known subject combinations
            Cache::forget('api_subjects_gall_sall');
            Cache::forget('api_chapters_gall_sall');

            // 3. Clear all grade-specific and stream-specific permutations
            $gradeIds = Grade::pluck('id')->all();
            $gradeIds[] = 'all';

            $subjectIds = Subject::pluck('id')->all();
            $subjectIds[] = 'all';

            $streamIds = [1, 2, 3, 4, 'all', null];

            foreach ($gradeIds as $gId) {
                Cache::forget("api_chapters_grade_{$gId}");

                foreach ($streamIds as $sId) {
                    $streamKey = $sId ?? 'all';
                    Cache::forget("api_subjects_g{$gId}_s{$streamKey}");
                }

                foreach ($subjectIds as $subjId) {
                    Cache::forget("api_chapters_g{$gId}_s{$subjId}");
                }
            }

            foreach ($subjectIds as $subjId) {
                Cache::forget("api_chapters_subject_{$subjId}");
            }
        } catch (\Throwable $e) {
            Log::warning('CurriculumCacheService clearAll error: ' . $e->getMessage());
        }
    }

    /**
     * Clear caches specific to a grade and/or subject.
     */
    public static function clearForSubject(?int $gradeId, ?int $subjectId = null): void
    {
        Cache::forget('api_grades_with_subjects');
        Cache::forget('api_subjects_gall_sall');
        Cache::forget('api_chapters_gall_sall');

        if ($gradeId) {
            Cache::forget("api_chapters_grade_{$gradeId}");
            for ($s = 1; $s <= 4; $s++) {
                Cache::forget("api_subjects_g{$gradeId}_s{$s}");
            }
            Cache::forget("api_subjects_g{$gradeId}_sall");
        }

        if ($subjectId) {
            Cache::forget("api_chapters_subject_{$subjectId}");
            if ($gradeId) {
                Cache::forget("api_chapters_g{$gradeId}_s{$subjectId}");
            }
            Cache::forget("api_chapters_gall_s{$subjectId}");
        }

        // Also run full sweep in background or directly to ensure no stale listings
        self::clearAll();
    }
}
