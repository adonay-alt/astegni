<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class TutorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'gender',
        'city',
        'image',
        'status',
    ];

    protected $appends = [
        'average_rating',
        'review_count',
    ];

    /**
     * Get the full public URL for the tutor's image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }

    /**
     * Scope query to only approved tutors.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get the user that owns the tutor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviews for the tutor profile.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(TutorReview::class, 'tutor_profile_id');
    }

    /**
     * Get the subjects associated with the tutor profile.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_tutor_profile');
    }

    /**
     * Get the grades associated with the tutor profile.
     */
    public function grades(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class, 'grade_tutor_profile');
    }

    /**
     * The "booted" method of the model.
     * Register model event listeners for automatic image deletion.
     */
    protected static function booted(): void
    {
        static::deleting(function (TutorProfile $profile) {
            if ($profile->image
                && !str_starts_with($profile->image, 'http://')
                && !str_starts_with($profile->image, 'https://')) {
                if (Storage::disk('public')->exists($profile->image)) {
                    Storage::disk('public')->delete($profile->image);
                }
            }
        });
    }

    /**
     * Get average rating accessor.
     */
    public function getAverageRatingAttribute(): float
    {
        if ($this->relationLoaded('reviews')) {
            $reviews = $this->getRelation('reviews');
            return $reviews->isEmpty() ? 0.0 : round((float) $reviews->avg('rating'), 1);
        }

        return round((float) ($this->reviews()->avg('rating') ?? 0), 1);
    }

    /**
     * Get review count accessor.
     */
    public function getReviewCountAttribute(): int
    {
        if ($this->relationLoaded('reviews')) {
            return $this->getRelation('reviews')->count();
        }

        return (int) $this->reviews()->count();
    }
}


