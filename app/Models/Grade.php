<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
        ];
    }

    /**
     * Determine if grade supports normal learning content.
     */
    public function supportsLearning(): bool
    {
        return (int) $this->type === 1 || (int) $this->type === 2;
    }

    /**
     * Determine if grade supports National Exam content.
     */
    public function supportsNationalExam(): bool
    {
        return (int) $this->type === 0 || (int) $this->type === 2;
    }

    /**
     * Determine if grade can be assigned to a user (Grades 9, 10, 11, 12 only).
     */
    public function isUserAssignable(): bool
    {
        if (!$this->supportsLearning()) {
            return false;
        }

        preg_match('/\d+/', $this->name, $matches);
        if (!empty($matches)) {
            $num = (int) $matches[0];
            return in_array($num, [9, 10, 11, 12], true);
        }

        return false;
    }

    /**
     * Get the streams for the grade.
     */
     protected $hidden = ['created_at', 'updated_at'];
    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    /**
     * Get the subjects for the grade.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Get the chapters for the grade.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * Get the notes for the grade.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the textbooks for the grade.
     */
    public function textbooks(): HasMany
    {
        return $this->hasMany(Textbook::class);
    }

    /**
     * Get the resources for the grade.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Get the videos for the grade.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get the quizzes for the grade.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Get the student profiles for the grade.
     */
    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    /**
     * Get the national exams for the grade.
     */
    public function nationalExams(): HasMany
    {
        return $this->hasMany(NationalExam::class);
    }

    /**
     * Get the national exam years for the grade.
     */
    public function nationalExamYears(): HasMany
    {
        return $this->hasMany(NationalExamYear::class);
    }

    /**
     * Get the national exam subjects for the grade.
     */
    public function nationalExamSubjects(): HasMany
    {
        return $this->hasMany(NationalExamSubject::class);
    }

    /**
     * Get the national exam questions for the grade.
     */
    public function nationalExamQuestions(): HasMany
    {
        return $this->hasMany(NationalExamQuestion::class);
    }

    /**
     * Get the national exam results for the grade.
     */
    public function nationalExamResults(): HasMany
    {
        return $this->hasMany(NationalExamResult::class);
    }

    /**
     * Get the tutor profiles associated with the grade.
     */
    public function tutorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(TutorProfile::class, 'grade_tutor_profile');
    }
}
