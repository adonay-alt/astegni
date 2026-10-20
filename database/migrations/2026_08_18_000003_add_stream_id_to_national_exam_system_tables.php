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
        Schema::table('national_exams', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('grade_id')->constrained('streams')->cascadeOnDelete();
        });

        Schema::table('national_exam_years', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('grade_id')->constrained('streams')->cascadeOnDelete();
        });

        Schema::table('national_exam_subjects', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('grade_id')->constrained('streams')->cascadeOnDelete();
        });

        Schema::table('national_exam_questions', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->after('grade_id')->constrained('streams')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('national_exam_questions', function (Blueprint $table) {
            $table->dropForeign(['stream_id']);
            $table->dropColumn('stream_id');
        });

        Schema::table('national_exam_subjects', function (Blueprint $table) {
            $table->dropForeign(['stream_id']);
            $table->dropColumn('stream_id');
        });

        Schema::table('national_exam_years', function (Blueprint $table) {
            $table->dropForeign(['stream_id']);
            $table->dropColumn('stream_id');
        });

        Schema::table('national_exams', function (Blueprint $table) {
            $table->dropForeign(['stream_id']);
            $table->dropColumn('stream_id');
        });
    }
};
