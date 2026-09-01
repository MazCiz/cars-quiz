<?php

namespace App\Actions;

use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Builds the aggregate metrics shown on /metrics: how long each question takes
 * on average and how often each answer is chosen (most-picked highlighted).
 */
class BuildQuizMetrics
{
    /**
     * @return array{total_attempts: int, questions: array<int, array<string, mixed>>}
     */
    public function handle(): array
    {
        $questions = QuizQuestion::orderBy('position')->get();

        // Average time + response count per question.
        $times = DB::table('quiz_answers')
            ->select('question_key', DB::raw('AVG(time_spent_ms) as avg_ms'), DB::raw('COUNT(*) as responses'))
            ->groupBy('question_key')
            ->get()
            ->keyBy('question_key');

        // Answer counts per (question, value).
        $counts = DB::table('quiz_answers')
            ->select('question_key', 'value', DB::raw('COUNT(*) as count'))
            ->groupBy('question_key', 'value')
            ->get()
            ->groupBy('question_key');

        $out = $questions->map(function ($q) use ($times, $counts) {
            $labels = collect($q->options)->pluck('label', 'value');

            $answerRows = ($counts->get($q->key) ?? collect())
                ->map(fn ($r) => [
                    'value' => $r->value,
                    'label' => $labels->get($r->value, $r->value),
                    'count' => (int) $r->count,
                ])
                ->sortByDesc('count')
                ->values();

            $t = $times->get($q->key);

            return [
                'key' => $q->key,
                'text' => $q->text,
                'avg_time_ms' => $t ? (int) round($t->avg_ms) : null,
                'responses' => $t ? (int) $t->responses : 0,
                'answers' => $answerRows,
                'top_answer' => $answerRows->first(),
            ];
        });

        return [
            'total_attempts' => QuizAttempt::count(),
            'questions' => $out->all(),
        ];
    }
}
