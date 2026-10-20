<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Textbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'subject_id',
        'chapter_id',
        'title',
        'description',
        'file_url',
    ];

    protected $appends = [
        'file_path',
        'file_size_mb',
    ];

    protected $hidden = ['updated_at'];
    /**
     * Get the grade that owns the textbook.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the subject that owns the textbook.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the chapter that owns the textbook.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Accessor to return full public URL for the textbook file.
     *
     * @param string|null $value
     * @return string
     */
    public function getFileUrlAttribute(?string $value): string
    {
        if (!$value) {
            return '';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        $path = ltrim($value, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }
        $path = ltrim($path, '/');
        // Fallback for un-normalized legacy folder names with spaces
        $path = str_replace(['textbooks/grade 10/', 'textbooks/Grade 10/'], 'textbooks/grade10/', $path);
        return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    }

    /**
     * Accessor to return relative storage path.
     */
    public function getFilePathAttribute(): string
    {
        return $this->getRawOriginal('file_url') ?? '';
    }

    /**
     * Accessor to calculate file size in MB.
     */
    public function getFileSizeMbAttribute(): ?float
    {
        $raw = $this->getRawOriginal('file_url');
        if (!$raw) {
            return null;
        }
        $path = ltrim($raw, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }
        $path = ltrim($path, '/');
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $bytes = \Illuminate\Support\Facades\Storage::disk('public')->size($path);
                return round($bytes / 1048576, 2);
            }
        } catch (\Throwable $e) {
            // Silently return null if file cannot be read
        }
        return null;
    }
}
