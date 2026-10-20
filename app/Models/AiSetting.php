<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AiSetting extends Model
{
    protected $table = 'ai_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    public const DEFAULT_NOTES_PROMPT = <<<'TEXT'
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

    public const DEFAULT_QUIZ_COUNT = 10;

    public const ALLOWED_PLACEHOLDERS = [
        '{chapter_title}',
        '{grade_level}',
        '{subject_name}',
    ];

    /**
     * Retrieve a setting by key, cached for 60 minutes.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "ai_setting_{$key}";

        try {
            return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                // Return default immediately if table does not exist
                if (!Schema::hasTable('ai_settings')) {
                    return $default;
                }

                $record = static::where('key', $key)->first();
                if (!$record || $record->value === null) {
                    return $default;
                }

                return static::castValue($record->value, $record->type);
            });
        } catch (\Throwable $e) {
            Log::warning("AiSetting::get failed for key '{$key}': " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Set/update a setting by key and invalidate its cache.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @param string|null $description
     * @return static
     */
    public static function set(string $key, $value, ?string $type = null, ?string $description = null): self
    {
        $payload = [
            'value' => is_array($value) ? json_encode($value) : (string) $value,
        ];

        if ($type !== null) {
            $payload['type'] = $type;
        }
        if ($description !== null) {
            $payload['description'] = $description;
        }

        $record = static::updateOrCreate(
            ['key' => $key],
            $payload
        );

        Cache::forget("ai_setting_{$key}");

        return $record;
    }

    /**
     * Invalidate cache for a specific key or all common AI settings.
     *
     * @param string|null $key
     * @return void
     */
    public static function purgeCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("ai_setting_{$key}");
        } else {
            Cache::forget("ai_setting_notes_prompt_template");
            Cache::forget("ai_setting_quiz_question_count");
        }
    }

    /**
     * Cast raw database string value to appropriate type.
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue(string $value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'text':
            default:
                return $value;
        }
    }

    /**
     * Helper to return default notes prompt.
     *
     * @return string
     */
    public static function getDefaultNotesPrompt(): string
    {
        return self::DEFAULT_NOTES_PROMPT;
    }
}
