<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'total_time_ms',
        'recommendation',
        'recommendation_source',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id')->orderBy('position');
    }
}
