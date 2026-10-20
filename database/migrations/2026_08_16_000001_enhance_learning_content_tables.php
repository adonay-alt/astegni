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
        // Temporarily drop compatibility views to allow table alterations in SQLite
        DB::statement('DROP VIEW IF EXISTS grade');
        DB::statement('DROP VIEW IF EXISTS subject');
        DB::statement('DROP VIEW IF EXISTS chapter');

        // 1. Add grade_id to chapters table
        if (!Schema::hasColumn('chapters', 'grade_id')) {
            Schema::table('chapters', function (Blueprint $table) {
                $table->foreignId('grade_id')->nullable()->after('id')->constrained('grades')->cascadeOnDelete();
            });

            DB::statement('UPDATE chapters SET grade_id = (SELECT grade_id FROM subjects WHERE subjects.id = chapters.subject_id) WHERE grade_id IS NULL');
        }

        // 2. Add grade_id and subject_id to notes table
        if (!Schema::hasColumn('notes', 'grade_id')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->foreignId('grade_id')->nullable()->after('id')->constrained('grades')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->after('grade_id')->constrained('subjects')->cascadeOnDelete();
            });

            DB::statement('UPDATE notes SET subject_id = (SELECT subject_id FROM chapters WHERE chapters.id = notes.chapter_id) WHERE subject_id IS NULL');
            DB::statement('UPDATE notes SET grade_id = (SELECT grade_id FROM chapters WHERE chapters.id = notes.chapter_id) WHERE grade_id IS NULL');
        }

        // 3. Add grade_id and subject_id to textbooks table
        if (!Schema::hasColumn('textbooks', 'grade_id')) {
            Schema::table('textbooks', function (Blueprint $table) {
                $table->foreignId('grade_id')->nullable()->after('id')->constrained('grades')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->after('grade_id')->constrained('subjects')->cascadeOnDelete();
            });

            DB::statement('UPDATE textbooks SET subject_id = (SELECT subject_id FROM chapters WHERE chapters.id = textbooks.chapter_id) WHERE subject_id IS NULL');
            DB::statement('UPDATE textbooks SET grade_id = (SELECT grade_id FROM chapters WHERE chapters.id = textbooks.chapter_id) WHERE grade_id IS NULL');
        }

        // 4. Add grade_id and subject_id to resources table
        if (!Schema::hasColumn('resources', 'grade_id')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->foreignId('grade_id')->nullable()->after('id')->constrained('grades')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->after('grade_id')->constrained('subjects')->cascadeOnDelete();
            });

            DB::statement('UPDATE resources SET subject_id = (SELECT subject_id FROM chapters WHERE chapters.id = resources.chapter_id) WHERE subject_id IS NULL');
            DB::statement('UPDATE resources SET grade_id = (SELECT grade_id FROM chapters WHERE chapters.id = resources.chapter_id) WHERE grade_id IS NULL');
        }

        // 5. Add grade_id and subject_id to quizzes table
        if (!Schema::hasColumn('quizzes', 'grade_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->foreignId('grade_id')->nullable()->after('id')->constrained('grades')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->after('grade_id')->constrained('subjects')->cascadeOnDelete();
            });

            DB::statement('UPDATE quizzes SET subject_id = (SELECT subject_id FROM chapters WHERE chapters.id = quizzes.chapter_id) WHERE subject_id IS NULL');
            DB::statement('UPDATE quizzes SET grade_id = (SELECT grade_id FROM chapters WHERE chapters.id = quizzes.chapter_id) WHERE grade_id IS NULL');
        }

        // 6. Create videos table
        if (!Schema::hasTable('videos')) {
            Schema::create('videos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('video_url');
                $table->string('thumbnail_url')->nullable();
                $table->string('duration')->nullable();
                $table->timestamps();
            });
        }

        // Recreate compatibility views for singular joins
        DB::statement('CREATE VIEW grade AS SELECT * FROM grades');
        DB::statement('CREATE VIEW subject AS SELECT * FROM subjects');
        DB::statement('CREATE VIEW chapter AS SELECT * FROM chapters');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS grade');
        DB::statement('DROP VIEW IF EXISTS subject');
        DB::statement('DROP VIEW IF EXISTS chapter');

        Schema::dropIfExists('videos');

        if (Schema::hasColumn('quizzes', 'grade_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropForeign(['grade_id']);
                $table->dropForeign(['subject_id']);
                $table->dropColumn(['grade_id', 'subject_id']);
            });
        }

        if (Schema::hasColumn('resources', 'grade_id')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->dropForeign(['grade_id']);
                $table->dropForeign(['subject_id']);
                $table->dropColumn(['grade_id', 'subject_id']);
            });
        }

        if (Schema::hasColumn('textbooks', 'grade_id')) {
            Schema::table('textbooks', function (Blueprint $table) {
                $table->dropForeign(['grade_id']);
                $table->dropForeign(['subject_id']);
                $table->dropColumn(['grade_id', 'subject_id']);
            });
        }

        if (Schema::hasColumn('notes', 'grade_id')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->dropForeign(['grade_id']);
                $table->dropForeign(['subject_id']);
                $table->dropColumn(['grade_id', 'subject_id']);
            });
        }

        if (Schema::hasColumn('chapters', 'grade_id')) {
            Schema::table('chapters', function (Blueprint $table) {
                $table->dropForeign(['grade_id']);
                $table->dropColumn('grade_id');
            });
        }

        DB::statement('CREATE VIEW grade AS SELECT * FROM grades');
        DB::statement('CREATE VIEW subject AS SELECT * FROM subjects');
        DB::statement('CREATE VIEW chapter AS SELECT * FROM chapters');
    }
};
