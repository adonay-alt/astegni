<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'national_exam_id',
        'national_exam_year_id',
        'national_exam_subject_id',
        'grade_id',
        'stream_id',
        'user_id',
        'score',
        'total_questions',
        'percentage',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_questions' => 'integer',
        'percentage' => 'float',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the result.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the national exam that owns the result.
     */
    public function nationalExam(): BelongsTo
    {
        return $this->belongsTo(NationalExam::class);
    }

    /**
     * Get the national exam year that owns the result.
     */
    public function nationalExamYear(): BelongsTo
    {
        return $this->belongsTo(NationalExamYear::class);
    }

    /**
     * Get the national exam subject that owns the result.
     */
    public function nationalExamSubject(): BelongsTo
    {
        return $this->belongsTo(NationalExamSubject::class);
    }

    /**
     * Get the grade that owns the result.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the stream that owns the result.
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }
}
