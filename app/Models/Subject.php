<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'stream_id',
        'title',
        'description',
    ];

    /**
     * Get the grade that owns the subject.
     */
     protected $hidden = ['created_at', 'updated_at'];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['name'];

    /**
     * Compatibility accessor for name (alias of title).
     */
    public function getNameAttribute(): ?string
    {
        return $this->title;
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the stream that owns the subject.
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    /**
     * Get the chapters for the subject.
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * Get the notes for the subject.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the textbooks for the subject.
     */
    public function textbooks(): HasMany
    {
        return $this->hasMany(Textbook::class);
    }

    /**
     * Get the resources for the subject.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Get the videos for the subject.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get the quizzes for the subject.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Get the tutor profiles associated with the subject.
     */
    public function tutorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(TutorProfile::class, 'subject_tutor_profile');
    }
}

