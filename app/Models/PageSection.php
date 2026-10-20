<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'heading',
        'heading_am',
        'body',
        'body_am',
        'question',
        'question_am',
        'answer',
        'answer_am',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'heading_en',
        'body_en',
        'question_en',
        'answer_en',
    ];

    /**
     * Accessor for heading_en.
     */
    public function getHeadingEnAttribute(): ?string
    {
        return $this->attributes['heading'] ?? null;
    }

    /**
     * Mutator for heading_en.
     */
    public function setHeadingEnAttribute(?string $value): void
    {
        $this->attributes['heading'] = $value;
    }

    /**
     * Accessor for body_en.
     */
    public function getBodyEnAttribute(): ?string
    {
        return $this->attributes['body'] ?? null;
    }

    /**
     * Mutator for body_en.
     */
    public function setBodyEnAttribute(?string $value): void
    {
        $this->attributes['body'] = $value;
    }

    /**
     * Accessor for question_en.
     */
    public function getQuestionEnAttribute(): ?string
    {
        return $this->attributes['question'] ?? null;
    }

    /**
     * Mutator for question_en.
     */
    public function setQuestionEnAttribute(?string $value): void
    {
        $this->attributes['question'] = $value;
    }

    /**
     * Accessor for answer_en.
     */
    public function getAnswerEnAttribute(): ?string
    {
        return $this->attributes['answer'] ?? null;
    }

    /**
     * Mutator for answer_en.
     */
    public function setAnswerEnAttribute(?string $value): void
    {
        $this->attributes['answer'] = $value;
    }

    /**
     * Resolve localized heading with fallback to English.
     */
    public function getLocalizedHeading(string $locale = 'en'): ?string
    {
        if ($locale === 'am' && !empty(trim($this->heading_am ?? ''))) {
            return $this->heading_am;
        }

        return $this->heading;
    }

    /**
     * Resolve localized body with fallback to English.
     */
    public function getLocalizedBody(string $locale = 'en'): ?string
    {
        if ($locale === 'am' && !empty(trim($this->body_am ?? ''))) {
            return $this->body_am;
        }

        return $this->body;
    }

    /**
     * Resolve localized question with fallback to English.
     */
    public function getLocalizedQuestion(string $locale = 'en'): ?string
    {
        if ($locale === 'am' && !empty(trim($this->question_am ?? ''))) {
            return $this->question_am;
        }

        return $this->question;
    }

    /**
     * Resolve localized answer with fallback to English.
     */
    public function getLocalizedAnswer(string $locale = 'en'): ?string
    {
        if ($locale === 'am' && !empty(trim($this->answer_am ?? ''))) {
            return $this->answer_am;
        }

        return $this->answer;
    }

    /**
     * Get the page that owns the section.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
