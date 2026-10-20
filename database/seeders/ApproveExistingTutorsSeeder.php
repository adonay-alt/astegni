<?php

namespace Database\Seeders;

use App\Models\TutorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApproveExistingTutorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Update existing pending tutor profiles to approved status.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $updatedCount = TutorProfile::where('status', 'pending')
                ->update(['status' => 'approved']);

            if ($this->command) {
                $this->command->info("Approved {$updatedCount} existing pending tutor profile(s).");
            }
        });
    }
}
