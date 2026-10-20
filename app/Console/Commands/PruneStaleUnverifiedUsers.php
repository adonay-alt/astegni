<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneStaleUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:prune-unverified 
                            {--days=30 : The age in days of unverified accounts to prune (default: 30)} 
                            {--dry-run : Simulate pruning without deleting records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely prune stale unverified user accounts older than a configurable threshold.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('The --days option must be an integer greater than 0.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Scanning for unverified accounts created on or before {$cutoff->toIso8601String()} ({$days} days old)...");

        // Query only accounts that are strictly unverified, non-admin, and have no critical activity
        $candidates = User::whereNull('email_verified_at')
            ->where('is_admin', false)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('tutorProfile')
            ->whereDoesntHave('quizResults')
            ->whereDoesntHave('nationalExamResults')
            ->get();

        $count = $candidates->count();

        if ($count === 0) {
            $this->info('No stale unverified accounts found matching the criteria.');
            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("[DRY RUN] Found {$count} stale unverified account(s) eligible for pruning:");
            foreach ($candidates as $user) {
                $this->line(" - ID: {$user->id} | Email: {$user->email} | Created: {$user->created_at}");
            }
            $this->info('[DRY RUN] No records were deleted.');
            return self::SUCCESS;
        }

        $prunedCount = 0;
        foreach ($candidates as $user) {
            $userId = $user->id;
            $userEmail = $user->email;
            $createdAt = $user->created_at;

            $user->delete();
            $prunedCount++;

            Log::info("Pruned stale unverified user: ID {$userId}, Email: {$userEmail}, Created At: {$createdAt}");
        }

        $this->info("Successfully pruned {$prunedCount} stale unverified user account(s).");
        return self::SUCCESS;
    }
}
