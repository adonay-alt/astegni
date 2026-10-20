<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NationalExamYear extends Model
{
    use HasFactory;

    protected $table = 'national_exam_years';

    protected $fillable = [
        'national_exam_id',
        'grade_id',
        'year',
    ];

    /**
     * Get the national exam that owns the exam year.
     */
    public function nationalExam(): BelongsTo
    {
        return $this->belongsTo(NationalExam::class);
    }

    /**
     * Get the grade that owns the exam year.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the exam subjects for this exam year.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(NationalExamSubject::class);
    }

    /**
     * Get the results for this exam year.
     */
    public function results(): HasMany
    {
        return $this->hasMany(NationalExamResult::class);
    }

    /**
     * Get the exam questions for this exam year.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(NationalExamQuestion::class);
    }
}
