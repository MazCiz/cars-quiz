<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSubmission extends Model
{
    protected $fillable = ['answers', 'recommendation', 'recommendation_source'];

    protected $casts = [
        'answers' => 'array',
    ];
}
