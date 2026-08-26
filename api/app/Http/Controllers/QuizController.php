<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    /** GET /api/quiz/questions — the ordered list of questions with options. */
    public function questions(): JsonResponse
    {
        $questions = QuizQuestion::orderBy('position')
            ->get(['key', 'text', 'options']);

        return response()->json(['questions' => $questions]);
    }

    /**
     * POST /api/quiz/submit — accept the completed attempt (each answer with the
     * time spent on it in milliseconds), generate a recommendation, store the
     * attempt + answers, and return the recommendation.
     *
     * Body: { "answers": [ { "key", "value", "time_spent_ms" }, ... ] }
     */
    public function submit(Request $request, RecommendationService $recommender): JsonResponse
    {
        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.key' => ['required', 'string'],
            'answers.*.value' => ['required', 'string'],
            // Millisecond precision; capped at a day to reject nonsense.
            'answers.*.time_spent_ms' => ['required', 'integer', 'min:0', 'max:86400000'],
        ]);

        $questions = QuizQuestion::orderBy('position')->get()->keyBy('key');

        // Validate each answer against its question's options, and index by key.
        $byKey = [];
        foreach ($data['answers'] as $a) {
            $q = $questions->get($a['key']);
            if (! $q) {
                throw ValidationException::withMessages(['answers' => "Unknown question '{$a['key']}'."]);
            }
            $allowed = collect($q->options)->pluck('value')->all();
            if (! in_array($a['value'], $allowed, true)) {
                throw ValidationException::withMessages(['answers' => "Invalid answer for '{$a['key']}'."]);
            }
            $byKey[$a['key']] = $a;
        }

        // Every question must be answered exactly once.
        foreach ($questions as $key => $q) {
            if (! isset($byKey[$key])) {
                throw ValidationException::withMessages(['answers' => "Missing answer for '{$key}'."]);
            }
        }

        // key => value map (question order) for the recommender.
        $answerMap = $questions->mapWithKeys(fn ($q, $key) => [$key => $byKey[$key]['value']])->all();
        $result = $recommender->generate($answerMap);

        $totalMs = array_sum(array_map(fn ($a) => (int) $a['time_spent_ms'], $byKey));

        $attempt = DB::transaction(function () use ($questions, $byKey, $result, $totalMs) {
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

        return response()->json([
            'id' => $attempt->id,
            'recommendation' => $result['text'],
            'source' => $result['source'],
            'total_time_ms' => $totalMs,
        ]);
    }
}
