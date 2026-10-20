<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NationalExamSubject extends Model
{
    use HasFactory;

    protected $table = 'national_exam_subjects';

    protected $fillable = [
        'national_exam_id',
        'national_exam_year_id',
        'grade_id',
        'stream_id',
        'name',
        'description',
    ];

    /**
     * Get the national exam that owns the subject.
     */
    public function nationalExam(): BelongsTo
    {
        return $this->belongsTo(NationalExam::class, 'national_exam_id');
    }

    /**
     * Get the national exam year that owns the subject.
     */
    public function nationalExamYear(): BelongsTo
    {
        return $this->belongsTo(NationalExamYear::class, 'national_exam_year_id');
    }

    /**
     * Get the grade that owns the subject.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    /**
     * Get the stream that owns the subject (only subjects has stream_id).
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    /**
     * Scope: Fetches subjects suitable for a given stream ID (includes Common Core where stream_id is NULL)
     */
    public function scopeForStream($query, $streamId = null)
    {
        if (!$streamId) {
            return $query->whereNull('stream_id');
        }
        return $query->where(function ($q) use ($streamId) {
            $q->where('stream_id', $streamId)
              ->orWhereNull('stream_id');
        });
    }

    /**
     * Get the exam questions for this subject.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(NationalExamQuestion::class);
    }

    /**
     * Get the results for this exam subject.
     */
    public function results(): HasMany
    {
        return $this->hasMany(NationalExamResult::class);
    }
}
