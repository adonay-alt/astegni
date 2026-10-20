<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'ai_premium_notes')) {
                $table->longText('ai_premium_notes')->nullable()->after('notes_generated_at');
            }
            if (!Schema::hasColumn('chapters', 'premium_notes_generated_at')) {
                $table->timestamp('premium_notes_generated_at')->nullable()->after('ai_premium_notes');
            }
        });

        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->index()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('chapters', 'premium_notes_generated_at')) {
                $columnsToDrop[] = 'premium_notes_generated_at';
            }
            if (Schema::hasColumn('chapters', 'ai_premium_notes')) {
                $columnsToDrop[] = 'ai_premium_notes';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'is_premium')) {
                $table->dropIndex(['is_premium']);
                $table->dropColumn('is_premium');
            }
        });
    }
};
