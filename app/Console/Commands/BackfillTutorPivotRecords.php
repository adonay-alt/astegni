<?php

namespace App\Console\Commands;

use App\Models\Grade;
use App\Models\Subject;
use App\Models\TutorProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTutorPivotRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tutors:backfill-pivot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing pivot records (subjects and grades) for tutor profiles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking tutor profiles for missing subject/grade pivot relations...');

        $defaultSubjectIds = Subject::query()->limit(2)->pluck('id')->all();
        $defaultGradeIds = Grade::query()->limit(2)->pluck('id')->all();

        if (empty($defaultSubjectIds)) {
            $this->warn('No subjects found in subjects table to backfill.');
        }

        if (empty($defaultGradeIds)) {
            $this->warn('No grades found in grades table to backfill.');
        }

        $tutors = TutorProfile::with(['subjects', 'grades'])->get();

        if ($tutors->isEmpty()) {
            $this->info('No tutor profiles found in database.');
            return 0;
        }

        $updatedCount = 0;

        DB::transaction(function () use ($tutors, $defaultSubjectIds, $defaultGradeIds, &$updatedCount) {
            foreach ($tutors as $tutor) {
                $modified = false;

                if ($tutor->subjects->count() === 0 && !empty($defaultSubjectIds)) {
                    $tutor->subjects()->sync($defaultSubjectIds);
                    $modified = true;
                }

                if ($tutor->grades->count() === 0 && !empty($defaultGradeIds)) {
                    $tutor->grades()->sync($defaultGradeIds);
                    $modified = true;
                }

                if ($modified) {
                    $updatedCount++;
                }
            }
        });

        $this->info("Successfully backfilled pivot records for {$updatedCount} tutor profile(s).");

        return 0;
    }
}
