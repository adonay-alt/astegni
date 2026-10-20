<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LatestUpdate extends Model
{
    use HasFactory;

    protected $table = 'latest_updates';

    protected $fillable = [
        'title',
        'title_am',
        'description',
        'description_am',
        'image_path',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
        'title_en',
        'description_en',
        'is_published',
    ];

    /**
     * Accessor for is_published (synonym for is_active).
     */
    public function getIsPublishedAttribute(): bool
    {
        return (bool) ($this->attributes['is_active'] ?? false);
    }

    /**
     * Mutator for is_published.
     */
    public function setIsPublishedAttribute($value): void
    {
        $this->attributes['is_active'] = (bool) $value;
    }

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
     * Accessor for description_en.
     */
    public function getDescriptionEnAttribute(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    /**
     * Mutator for description_en.
     */
    public function setDescriptionEnAttribute(?string $value): void
    {
        $this->attributes['description'] = $value;
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
     * Resolve localized description with fallback to English.
     */
    public function getLocalizedDescription(string $locale = 'en'): string
    {
        if ($locale === 'am' && !empty(trim($this->description_am ?? ''))) {
            return $this->description_am;
        }

        return (string) ($this->description ?? '');
    }

    /**
     * Accessor to return full public URL for the update image.
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        $path = ltrim($this->image_path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }
        $path = ltrim($path, '/');

        return Storage::disk('public')->url($path);
    }
}
