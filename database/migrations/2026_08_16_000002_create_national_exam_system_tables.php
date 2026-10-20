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
        // 1. national_exams
        Schema::create('national_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        // 2. national_exam_years
        Schema::create('national_exam_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('national_exam_id')->constrained('national_exams')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->integer('year');
            $table->timestamps();
        });

        // 3. national_exam_subjects
        Schema::create('national_exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('national_exam_id')->constrained('national_exams')->cascadeOnDelete();
            $table->foreignId('national_exam_year_id')->constrained('national_exam_years')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. national_exam_questions
        Schema::create('national_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('national_exam_id')->constrained('national_exams')->cascadeOnDelete();
            $table->foreignId('national_exam_year_id')->constrained('national_exam_years')->cascadeOnDelete();
            $table->foreignId('national_exam_subject_id')->constrained('national_exam_subjects')->cascadeOnDelete();
            $table->integer('question_number');
            $table->text('question');
            $table->text('option_a')->nullable();
            $table->text('option_b')->nullable();
            $table->text('option_c')->nullable();
            $table->text('option_d')->nullable();
            $table->string('correct_answer');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_exam_questions');
        Schema::dropIfExists('national_exam_subjects');
        Schema::dropIfExists('national_exam_years');
        Schema::dropIfExists('national_exams');
    }
};
