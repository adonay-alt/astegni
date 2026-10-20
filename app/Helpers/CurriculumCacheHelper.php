<?php

namespace App\Helpers;

use App\Services\CurriculumCacheService;
use Illuminate\Support\Facades\Cache;

class CurriculumCacheHelper
{
    /**
     * Completely flush and clear all curriculum caches (grades, subjects, chapters, textbooks).
     */
    public static function clearAllCurriculumCache(): void
    {
        // 1. Clear known keys and combinations
        CurriculumCacheService::clearAll();

        // 2. Perform total cache flush to guarantee zero stale keys
        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Silently continue if flush fails on specific driver
        }
    }

    /**
     * Clear caches specific to a grade and/or subject.
     */
    public static function clearForSubject(?int $gradeId, ?int $subjectId = null): void
    {
        CurriculumCacheService::clearForSubject($gradeId, $subjectId);

        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Silently continue
        }
    }
}
