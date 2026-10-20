<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ai_settings')) {
            Schema::create('ai_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique()->index();
                $table->longText('value')->nullable();
                $table->string('type')->default('text'); // 'text', 'integer', 'json'
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        $defaultNotesPrompt = <<<'TEXT'
You are an expert high school educator specializing in the Ethiopian National Curriculum (Ministry of Education standards).
Analyze the attached textbook chapter PDF titled "{chapter_title}" for {grade_level}.

Generate comprehensive, well-structured, clear revision notes for Grade 9-12 high school students covering:
1. **Key Concepts & Essential Definitions**: Clear, concise explanations of terms and terminology.
2. **Core Lessons & Detailed Explanations**: In-depth bullet points, essential formulas, principles, or rules explained clearly.
3. **Worked Examples & Practical Applications**: Step-by-step solutions for sample problems, real-world examples, or diagram walkthroughs.
4. **Exam Revision Summary**: Quick key takeaways and summary points for national exam prep.
5. **Practice / Self-Test Questions**: Review questions with short, accurate answer keys at the bottom.

Formatting Rules:
- Format the output strictly in clean Markdown (H2, H3, bullet points, bold text, LaTeX math syntax if relevant).
- Do NOT enclose the overall output in ```markdown ``` triple backtick code blocks.
TEXT;

        // Seed default keys if not present
        if (Schema::hasTable('ai_settings')) {
            if (!DB::table('ai_settings')->where('key', 'notes_prompt_template')->exists()) {
                DB::table('ai_settings')->insert([
                    'key' => 'notes_prompt_template',
                    'value' => $defaultNotesPrompt,
                    'type' => 'text',
                    'description' => 'System prompt template used by Gemini AI when generating chapter revision notes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (!DB::table('ai_settings')->where('key', 'quiz_question_count')->exists()) {
                DB::table('ai_settings')->insert([
                    'key' => 'quiz_question_count',
                    'value' => '10',
                    'type' => 'integer',
                    'description' => 'Default number of multiple-choice questions generated per chapter quiz (min: 3, max: 30).',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
