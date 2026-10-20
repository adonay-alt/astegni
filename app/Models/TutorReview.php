<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_profile_id',
        'student_id',
        'rating',
        'review',
    ];

    /**
     * Get the tutor profile associated with the review.
     */
    public function tutorProfile(): BelongsTo
    {
        return $this->belongsTo(TutorProfile::class, 'tutor_profile_id');
    }

    /**
     * Get the student who wrote the review.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
