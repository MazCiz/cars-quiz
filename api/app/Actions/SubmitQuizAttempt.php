<?php

namespace App\Actions;

use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\RecommendationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns a validated quiz submission into a stored attempt with a recommendation.
 *
 * Validation (that every question is answered exactly once with an allowed
 * value) is guaranteed by SubmitQuizRequest, so this action can focus purely on
 * generating the recommendation and persisting the attempt + its answers.
 */
class SubmitQuizAttempt
{
    public function __construct(private RecommendationService $recommender) {}

    /**
     * @param  array<int, array{key: string, value: string, time_spent_ms: int|string}>  $answers
     * @param  Collection<string, QuizQuestion>  $questions  display order, keyed by slug
     */
    public function handle(array $answers, Collection $questions): QuizAttempt
    {
        $byKey = collect($answers)->keyBy('key');

        // slug => chosen value, in question order, for the recommender.
        $answerMap = $questions->map(fn ($q, $key) => (string) $byKey[$key]['value'])->all();

        $result = $this->recommender->generate($answerMap, $questions);

        $totalMs = collect($answers)->sum(fn ($a) => (int) $a['time_spent_ms']);

        return DB::transaction(function () use ($questions, $byKey, $result, $totalMs) {
            $attempt = QuizAttempt::create([
                'total_time_ms' => $totalMs,
                'recommendation' => $result['text'],
                'recommendation_source' => $result['source'],
                'completed_at' => now(),
            ]);

            $position = 0;
            foreach ($questions as $key => $q) {
                $position++;
                $attempt->answers()->create([
                    'position' => $position,
                    'question_key' => $key,
                    'value' => $byKey[$key]['value'],
                    'time_spent_ms' => (int) $byKey[$key]['time_spent_ms'],
                ]);
            }

            return $attempt;
        });
    }
}
