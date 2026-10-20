<?php

namespace App\Models;

use App\Enums\PlatformReviewStatus;
use Illuminate\Database\Eloquent\Model;

class PlatformReview extends Model
{
    protected $fillable = ['user_id', 'rating', 'comment', 'status'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PlatformReviewStatus::class,
            'rating' => 'integer',
        ];
    }

    /**
     * Scope query to only approved platform reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', PlatformReviewStatus::APPROVED);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
