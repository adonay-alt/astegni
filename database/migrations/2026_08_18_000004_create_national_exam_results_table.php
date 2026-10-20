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
        Schema::create('national_exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('national_exam_id')->constrained('national_exams')->cascadeOnDelete();
            $table->foreignId('national_exam_year_id')->constrained('national_exam_years')->cascadeOnDelete();
            $table->foreignId('national_exam_subject_id')->constrained('national_exam_subjects')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained('streams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('score');
            $table->unsignedInteger('total_questions');
            $table->float('percentage');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(
                ['national_exam_id', 'national_exam_year_id', 'national_exam_subject_id', 'user_id'],
                'ne_results_exam_year_subject_user_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_exam_results');
    }
};
