<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the subjects for the stream.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Get the national exams for the stream.
     */
    public function nationalExams(): HasMany
    {
        return $this->hasMany(NationalExam::class);
    }

    /**
     * Get the national exam years for the stream.
     */
    public function nationalExamYears(): HasMany
    {
        return $this->hasMany(NationalExamYear::class);
    }

    /**
     * Get the national exam subjects for the stream.
     */
    public function nationalExamSubjects(): HasMany
    {
        return $this->hasMany(NationalExamSubject::class);
    }

    /**
     * Get the national exam questions for the stream.
     */
    public function nationalExamQuestions(): HasMany
    {
        return $this->hasMany(NationalExamQuestion::class);
    }

    /**
     * Get the national exam results for the stream.
     */
    public function nationalExamResults(): HasMany
    {
        return $this->hasMany(NationalExamResult::class);
    }
}
