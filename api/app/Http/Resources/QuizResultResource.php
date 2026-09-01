<?php

namespace App\Http\Resources;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The result of a completed attempt: the stored recommendation plus the
 * metadata the frontend renders.
 *
 * @mixin QuizAttempt
 */
class QuizResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recommendation' => $this->recommendation,
            'source' => $this->recommendation_source,
            'total_time_ms' => (int) $this->total_time_ms,
        ];
    }
}
