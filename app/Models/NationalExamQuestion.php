<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalExamQuestion extends Model
{
    use HasFactory;

    protected $table = 'national_exam_questions';

    protected $fillable = [
        'grade_id',
        'national_exam_id',
        'national_exam_year_id',
        'national_exam_subject_id',
        'question_number',
        'question',
        'question_text',
        'image_url',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'explanation',
    ];

    /**
     * Accessor for question_text if question is populated or vice versa.
     */
    public function getQuestionTextAttribute($value): ?string
    {
        return $value ?? $this->attributes['question'] ?? null;
    }

    public function getQuestionAttribute($value): ?string
    {
        return $value ?? $this->attributes['question_text'] ?? null;
    }

    /**
     * Get the grade that owns the question.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the national exam that owns the question.
     */
    public function nationalExam(): BelongsTo
    {
        return $this->belongsTo(NationalExam::class);
    }

    /**
     * Get the national exam year that owns the question.
     */
    public function nationalExamYear(): BelongsTo
    {
        return $this->belongsTo(NationalExamYear::class);
    }

    /**
     * Get the national exam subject that owns the question.
     */
    public function nationalExamSubject(): BelongsTo
    {
        return $this->belongsTo(NationalExamSubject::class);
    }
}
