<?php

namespace App\Http\Resources;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single quiz question as exposed to the frontend.
 *
 * @mixin QuizQuestion
 */
class QuizQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'text' => $this->text,
            'options' => $this->options,
        ];
    }
}
