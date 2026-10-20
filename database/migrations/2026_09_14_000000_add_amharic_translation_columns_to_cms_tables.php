<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safe & non-destructive: Adds nullable Amharic translation columns to dynamic CMS tables.
     */
    public function up(): void
    {
        // 1. Pages table: add Amharic title & subtitle
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (!Schema::hasColumn('pages', 'title_am')) {
                    $table->string('title_am')->nullable()->after('title');
                }
                if (!Schema::hasColumn('pages', 'subtitle_am')) {
                    $table->string('subtitle_am')->nullable()->after('subtitle');
                }
            });
        }

        // 2. Page Sections table: add Amharic heading, body, question, answer
        if (Schema::hasTable('page_sections')) {
            Schema::table('page_sections', function (Blueprint $table) {
                if (!Schema::hasColumn('page_sections', 'heading_am')) {
                    $table->string('heading_am')->nullable()->after('heading');
                }
                if (!Schema::hasColumn('page_sections', 'body_am')) {
                    $table->text('body_am')->nullable()->after('body');
                }
                if (!Schema::hasColumn('page_sections', 'question_am')) {
                    $table->text('question_am')->nullable()->after('question');
                }
                if (!Schema::hasColumn('page_sections', 'answer_am')) {
                    $table->text('answer_am')->nullable()->after('answer');
                }
            });
        }

        // 3. Latest Updates table: add Amharic title & description
        if (Schema::hasTable('latest_updates')) {
            Schema::table('latest_updates', function (Blueprint $table) {
                if (!Schema::hasColumn('latest_updates', 'title_am')) {
                    $table->string('title_am')->nullable()->after('title');
                }
                if (!Schema::hasColumn('latest_updates', 'description_am')) {
                    $table->text('description_am')->nullable()->after('description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('pages', 'title_am')) {
                    $columns[] = 'title_am';
                }
                if (Schema::hasColumn('pages', 'subtitle_am')) {
                    $columns[] = 'subtitle_am';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('page_sections')) {
            Schema::table('page_sections', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('page_sections', 'heading_am')) {
                    $columns[] = 'heading_am';
                }
                if (Schema::hasColumn('page_sections', 'body_am')) {
                    $columns[] = 'body_am';
                }
                if (Schema::hasColumn('page_sections', 'question_am')) {
                    $columns[] = 'question_am';
                }
                if (Schema::hasColumn('page_sections', 'answer_am')) {
                    $columns[] = 'answer_am';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('latest_updates')) {
            Schema::table('latest_updates', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('latest_updates', 'title_am')) {
                    $columns[] = 'title_am';
                }
                if (Schema::hasColumn('latest_updates', 'description_am')) {
                    $columns[] = 'description_am';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
