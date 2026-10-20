<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'subject_id',
        'chapter_id',
        'title',
        'type',
        'url',
        'description',
        'price',
    ];

    protected $appends = [
        'file_url',
        'file_path',
        'file_size_mb',
        'file_type',
        'is_locked',
    ];

    protected $hidden = [
        'updated_at',
        'url',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    /**
     * Get the grade that owns the resource.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the subject that owns the resource.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the chapter that owns the resource.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Accessor to return full public URL for the resource file.
     *
     * @param string|null $value
     * @return string
     */
    public function getUrlAttribute(?string $value): string
    {
        if (!$value) {
            return '';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($value);
    }

    /**
     * Accessor to determine if the resource is currently locked for the requesting user.
     *
     * @return bool
     */
    public function getIsLockedAttribute(): bool
    {
        $user = auth()->user();

        // Admins always have full access
        if ($user && (!empty($user->is_admin) || in_array($user->role, ['super_admin', 'academic_editor', 'admin']))) {
            return false;
        }

        // Free resources are never locked
        if (empty($this->price) || (float)$this->price <= 0) {
            return false;
        }

        // Unauthenticated users are locked out of paid resources
        if (!$user) {
            return true;
        }

        // Check if user has purchased this resource
        $hasPurchased = \App\Models\ResourcePurchase::where('user_id', $user->id)
            ->where('resource_id', $this->id)
            ->where('status', 'success')
            ->exists();

        return !$hasPurchased;
    }

    /**
     * Accessor to expose file_url in JSON response matching textbook interface.
     * Strictly returns null if resource is locked for requesting user.
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        // If resource is locked for this user, return null
        if ($this->is_locked) {
            return null;
        }

        // Otherwise return the public storage URL
        return $this->url;
    }

    /**
     * Accessor to return relative storage path. Guarded if locked.
     *
     * @return string
     */
    public function getFilePathAttribute(): string
    {
        if ($this->is_locked) {
            return '';
        }

        return $this->getRawOriginal('url') ?? '';
    }

    /**
     * Accessor to calculate file size in MB.
     */
    public function getFileSizeMbAttribute(): ?float
    {
        $raw = $this->getRawOriginal('url');
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
            // Silently return null
        }
        return null;
    }

    /**
     * Accessor to return file type.
     */
    public function getFileTypeAttribute(): string
    {
        return $this->type ?? 'pdf';
    }
}
