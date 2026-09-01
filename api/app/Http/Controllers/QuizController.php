<?php

namespace App\Http\Controllers;

use App\Actions\BuildQuizMetrics;
use App\Actions\SubmitQuizAttempt;
use App\Http\Requests\SubmitQuizRequest;
use App\Http\Requests\SuggestQuestionRequest;
use App\Http\Resources\QuizQuestionResource;
use App\Http\Resources\QuizResultResource;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizController extends Controller
{
    /** GET /quiz/questions — the ordered list of questions with options. */
    public function questions(): AnonymousResourceCollection
    {
        return QuizQuestionResource::collection(
            QuizQuestion::orderBy('position')->get()
        );
    }

    /**
     * POST /quiz/submit — validate the completed attempt, generate a
     * recommendation, store the attempt + answers, and return the result.
     * Validation and persistence live in SubmitQuizRequest / SubmitQuizAttempt.
     */
    public function submit(SubmitQuizRequest $request, SubmitQuizAttempt $action): QuizResultResource
    {
        $attempt = $action->handle(
            $request->validated('answers'),
            $request->questions(),
        );

        return new QuizResultResource($attempt);
    }

    /**
     * GET /quiz/metrics — aggregate stats across all attempts:
     * approximate time spent per question and how often each answer was chosen.
     */
    public function metrics(BuildQuizMetrics $action): JsonResponse
    {
        return response()->json(['data' => $action->handle()]);
    }

    /**
     * POST /quiz/suggestions — capture a user-suggested question. Stored in
     * its own table for review; never added to the live quiz.
     */
    public function suggest(SuggestQuestionRequest $request): JsonResponse
    {
        $suggestion = QuizQuestionSuggestion::create($request->validated());

        return response()->json(['data' => ['id' => $suggestion->id]], 201);
    }
}
