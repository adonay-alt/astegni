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
        if (Schema::hasColumn('streams', 'grade_id')) {
            // Drop foreign key constraint if it exists
            try {
                Schema::table('streams', function (Blueprint $table) {
                    $table->dropForeign(['grade_id']);
                });
            } catch (\Throwable $e) {
                // Ignore if foreign key constraint does not exist
            }

            // Drop composite unique constraint if it exists
            try {
                Schema::table('streams', function (Blueprint $table) {
                    $table->dropUnique(['grade_id', 'name']);
                });
            } catch (\Throwable $e) {
                // Ignore if unique index does not exist
            }

            // Drop the grade_id column
            Schema::table('streams', function (Blueprint $table) {
                $table->dropColumn('grade_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('streams', 'grade_id')) {
            Schema::table('streams', function (Blueprint $table) {
                $table->foreignId('grade_id')->nullable()->after('id')->constrained('grades')->cascadeOnDelete();
                $table->unique(['grade_id', 'name']);
            });
        }
    }
};
