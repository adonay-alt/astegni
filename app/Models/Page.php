<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'title_am',
        'subtitle',
        'subtitle_am',
        'type',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'title_en',
        'subtitle_en',
    ];

    /**
     * Accessor for title_en.
     */
    public function getTitleEnAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * Mutator for title_en.
     */
    public function setTitleEnAttribute(?string $value): void
    {
        $this->attributes['title'] = $value;
    }

    /**
     * Accessor for subtitle_en.
     */
    public function getSubtitleEnAttribute(): ?string
    {
        return $this->attributes['subtitle'] ?? null;
    }

    /**
     * Mutator for subtitle_en.
     */
    public function setSubtitleEnAttribute(?string $value): void
    {
        $this->attributes['subtitle'] = $value;
    }

    /**
     * Resolve localized title with fallback to English.
     */
    public function getLocalizedTitle(string $locale = 'en'): string
    {
        if ($locale === 'am' && !empty(trim($this->title_am ?? ''))) {
            return $this->title_am;
        }

        return (string) ($this->title ?? '');
    }

    /**
     * Resolve localized subtitle with fallback to English.
     */
    public function getLocalizedSubtitle(string $locale = 'en'): ?string
    {
        if ($locale === 'am' && !empty(trim($this->subtitle_am ?? ''))) {
            return $this->subtitle_am;
        }

        return $this->subtitle;
    }

    /**
     * Get the sections/items for the page.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order', 'asc');
    }
}
