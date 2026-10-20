<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NationalExam extends Model
{
    use HasFactory;

    protected $table = 'national_exams';

    protected $fillable = [
        'grade_id',
        'title',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Get the grade that owns the national exam.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the exam years for the national exam.
     */
    public function years(): HasMany
    {
        return $this->hasMany(NationalExamYear::class);
    }

    /**
     * Get the exam subjects for the national exam.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(NationalExamSubject::class);
    }

    /**
     * Get the exam questions for the national exam.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(NationalExamQuestion::class);
    }

    /**
     * Get the results for the national exam.
     */
    public function results(): HasMany
    {
        return $this->hasMany(NationalExamResult::class);
    }
}
