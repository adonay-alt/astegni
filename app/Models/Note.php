<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'subject_id',
        'chapter_id',
        'title',
        'content',
        'is_ai_generated',
        'is_active',
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
        'is_active' => 'boolean',
        'grade_id' => 'integer',
        'subject_id' => 'integer',
        'chapter_id' => 'integer',
    ];

    /**
     * Scope a query to only include active notes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include teacher notes.
     */
    public function scopeTeacherNotes($query)
    {
        return $query->where('is_ai_generated', false);
    }

    /**
     * Scope a query to only include AI notes.
     */
    public function scopeAiNotes($query)
    {
        return $query->where('is_ai_generated', true);
    }

    /**
     * Get the grade that owns the note.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the subject that owns the note.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the chapter that owns the note.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
