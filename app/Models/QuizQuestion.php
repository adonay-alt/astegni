<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'explanation',
    ];

    /**
     * Get the quiz that owns the question.
     */
     protected $hidden = ['created_at', 'updated_at'];
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
