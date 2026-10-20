<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'grade_id',
        'stream_id', // Updated from 'stream' to 'stream_id'
        'school',
        'phone',
        'photo',
        'gender',
        'date_of_birth',
        'city_town',
        'region',
        'parent_guardian_contact',
    ];

    protected $casts = [
        'stream_id' => 'integer',
        'grade_id' => 'integer',
        'user_id' => 'integer',
        'date_of_birth' => 'date',
    ];

    protected $appends = ['age', 'photo_url'];

    /**
     * The "booted" method of the model.
     * Register model event listeners for automatic photo deletion.
     */
    protected static function booted(): void
    {
        static::deleting(function (StudentProfile $profile) {
            if ($profile->photo
                && !str_starts_with($profile->photo, 'http://')
                && !str_starts_with($profile->photo, 'https://')) {
                if (Storage::disk('public')->exists($profile->photo)) {
                    Storage::disk('public')->delete($profile->photo);
                }
            }
        });
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? \Carbon\Carbon::parse($this->date_of_birth)->age : null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the stream (Natural Science / Social Science) associated with the student.
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }
}