<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['position', 'key', 'text', 'options'];

    protected $casts = [
        'options' => 'array',
    ];
}
