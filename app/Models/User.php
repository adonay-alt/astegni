<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'grade',
        'is_admin',
        'role',
        'is_active',
        'otp',
        'otp_expires_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'grade' => 'integer',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'otp_expires_at' => 'datetime',
        ];
    }

    /**
     * Get active status, defaulting to true if null.
     */
    public function getIsActiveAttribute($value): bool
    {
        return $value === null ? true : (bool) $value;
    }

    /**
     * The "booted" method of the model.
     * Register model event listeners for cascading profile deletion to trigger file cleanup.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $user->tutorProfile?->delete();
            $user->studentProfile?->delete();
        });
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the grade model associated with the user.
     */
    public function gradeRelation(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade', 'id');
    }

    /**
     * Get the grade model associated with the user.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade', 'id');
    }

    /**
     * Get the student profile associated with the user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get the tutor profile associated with the user.
     */
    public function tutorProfile(): HasOne
    {
        return $this->hasOne(TutorProfile::class);
    }

    /**
     * Get the quiz results associated with the user.
     */
    public function quizResults(): HasMany
    {
        return $this->hasMany(QuizResult::class);
    }

    /**
     * Get the national exam results associated with the user.
     */
    public function nationalExamResults(): HasMany
    {
        return $this->hasMany(NationalExamResult::class);
    }

    /**
     * Get the bookmarks associated with the user.
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Get the in-app notifications associated with the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    /**
     * Get the user's profile photo URL (checking student profile photo first, then tutor profile image).
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->relationLoaded('studentProfile') && $this->studentProfile && $this->studentProfile->photo_url) {
            return $this->studentProfile->photo_url;
        }

        if ($this->studentProfile && $this->studentProfile->photo_url) {
            return $this->studentProfile->photo_url;
        }

        if ($this->relationLoaded('tutorProfile') && $this->tutorProfile && $this->tutorProfile->image_url) {
            return $this->tutorProfile->image_url;
        }

        if ($this->tutorProfile && $this->tutorProfile->image_url) {
            return $this->tutorProfile->image_url;
        }

        return null;
    }

    /**
     * Get the resource purchases associated with the user.
     */
    public function resourcePurchases(): HasMany
    {
        return $this->hasMany(ResourcePurchase::class);
    }

    /**
     * Check if user has paid for the chapter's premium resource.
     *
     * @param int|string $chapterId
     * @return bool
     */
    public function hasPaidForChapterResource(int|string $chapterId): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return ResourcePurchase::where('user_id', $this->id)
            ->where('status', 'success')
            ->whereHas('resource', function ($query) use ($chapterId) {
                $query->where('chapter_id', $chapterId);
            })
            ->exists();
    }

    /**
     * Check if user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' || ($this->is_admin && empty($this->role));
    }

    /**
     * Check if user has a specific administrative role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has administrative staff privileges.
     */
    public function isStaff(): bool
    {
        return (bool) $this->is_admin || in_array($this->role, [
            'super_admin',
            'academic_editor',
            'tutor_moderator',
        ]);
    }
}


