<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'subject_id',
        'chapter_id',
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'duration',
    ];

    protected $appends = [
        'platform',
    ];

    /**
     * Get the grade that owns the video.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the subject that owns the video.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the chapter that owns the video.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Determine video host platform.
     */
    public function getPlatformAttribute(): string
    {
        $url = strtolower($this->video_url ?? '');
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        }
        if (str_ends_with($url, '.mp4') || str_ends_with($url, '.mov') || str_ends_with($url, '.webm')) {
            return 'direct';
        }
        return 'other';
    }
}
