<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('platform_reviews') && Schema::hasColumn('platform_reviews', 'status')) {
            // Clean up / normalize any existing invalid or null values to 'pending' before modifying column
            DB::table('platform_reviews')
                ->whereNull('status')
                ->orWhereNotIn('status', ['pending', 'approved', 'rejected'])
                ->update(['status' => 'pending']);

            Schema::table('platform_reviews', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('platform_reviews') && Schema::hasColumn('platform_reviews', 'status')) {
            Schema::table('platform_reviews', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }
};
