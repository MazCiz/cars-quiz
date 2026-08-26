<?php

namespace App\Http\Controllers;

use App\Models\QuizQuestion;
use App\Models\QuizSubmission;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     * POST /api/quiz/submit — accept answers, generate a recommendation,
     * store the submission, and return the recommendation.
     */
    public function submit(Request $request, RecommendationService $recommender): JsonResponse
    {
        $questions = QuizQuestion::orderBy('position')->get();

        // Build validation: every answer must be one of that question's option values.
        $rules = ['answers' => ['required', 'array']];
        foreach ($questions as $q) {
            $allowed = collect($q->options)->pluck('value')->all();
            $rules["answers.{$q->key}"] = ['required', Rule::in($allowed)];
        }

        $validated = $request->validate($rules);
        $answers = $validated['answers'];

        $result = $recommender->generate($answers);

        $submission = QuizSubmission::create([
            'answers' => $answers,
            'recommendation' => $result['text'],
            'recommendation_source' => $result['source'],
        ]);

        return response()->json([
            'id' => $submission->id,
            'recommendation' => $result['text'],
            'source' => $result['source'],
        ]);
    }
}
