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
        // 1. Chapters table indexes
        if (Schema::hasTable('chapters')) {
            Schema::table('chapters', function (Blueprint $table) {
                $table->index(['grade_id', 'subject_id'], 'idx_chapters_grade_subject');
                $table->index('subject_id', 'idx_chapters_subject_id');
            });
        }

        // 2. Notes table indexes
        if (Schema::hasTable('notes')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->index(['chapter_id', 'grade_id', 'subject_id'], 'idx_notes_chapter_grade_subject');
            });
        }

        // 3. Textbooks table indexes
        if (Schema::hasTable('textbooks')) {
            Schema::table('textbooks', function (Blueprint $table) {
                $table->index(['chapter_id', 'grade_id', 'subject_id'], 'idx_textbooks_chapter_grade_subject');
            });
        }

        // 4. Resources table indexes
        if (Schema::hasTable('resources')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->index(['chapter_id', 'grade_id', 'subject_id'], 'idx_resources_chapter_grade_subject');
            });
        }

        // 5. Videos table indexes
        if (Schema::hasTable('videos')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->index(['chapter_id', 'grade_id', 'subject_id'], 'idx_videos_chapter_grade_subject');
            });
        }

        // 6. Quizzes table indexes
        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->index(['chapter_id', 'grade_id', 'subject_id'], 'idx_quizzes_chapter_grade_subject');
            });
        }

        // 7. Subjects table indexes
        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                if (Schema::hasColumn('subjects', 'stream_id')) {
                    $table->index(['stream_id', 'grade_id'], 'idx_subjects_stream_grade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('chapters')) {
            Schema::table('chapters', function (Blueprint $table) {
                $table->dropIndex('idx_chapters_grade_subject');
                $table->dropIndex('idx_chapters_subject_id');
            });
        }

        if (Schema::hasTable('notes')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->dropIndex('idx_notes_chapter_grade_subject');
            });
        }

        if (Schema::hasTable('textbooks')) {
            Schema::table('textbooks', function (Blueprint $table) {
                $table->dropIndex('idx_textbooks_chapter_grade_subject');
            });
        }

        if (Schema::hasTable('resources')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->dropIndex('idx_resources_chapter_grade_subject');
            });
        }

        if (Schema::hasTable('videos')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->dropIndex('idx_videos_chapter_grade_subject');
            });
        }

        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropIndex('idx_quizzes_chapter_grade_subject');
            });
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'stream_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropIndex('idx_subjects_stream_grade');
            });
        }
    }
};
