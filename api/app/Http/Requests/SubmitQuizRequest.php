<?php

namespace App\Http\Requests;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

/**
 * Validates a completed quiz submission.
 *
 * Body: { "answers": [ { "key", "value", "time_spent_ms" }, ... ] }
 *
 * Structural rules live in rules(); the semantic checks (does the question
 * exist, is the chosen value one of its options, are all questions answered)
 * run in withValidator() so each failure points at the specific offending
 * field — e.g. "answers.2.value" — rather than a single generic message.
 */
class SubmitQuizRequest extends FormRequest
{
    /** Memoized questions, keyed by slug — read once and reused by the action. */
    private ?Collection $questions = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            // "distinct" rejects the same question appearing twice instead of
            // silently letting a later answer overwrite an earlier one.
            'answers.*.key' => ['required', 'string', 'distinct'],
            'answers.*.value' => ['required', 'string'],
            // Millisecond precision; capped at a day to reject nonsense.
            'answers.*.time_spent_ms' => ['required', 'integer', 'min:0', 'max:86400000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $answers = $this->input('answers');
            if (! is_array($answers)) {
                return; // structural rules already failed
            }

            $questions = $this->questions();
            $answered = [];

            foreach ($answers as $i => $answer) {
                $key = $answer['key'] ?? null;
                if (! is_string($key)) {
                    continue; // handled by rules()
                }

                $question = $questions->get($key);
                if (! $question) {
                    $validator->errors()->add("answers.{$i}.key", "Unknown question '{$key}'.");

                    continue;
                }

                $allowed = collect($question->options)->pluck('value')->all();
                if (! in_array($answer['value'] ?? null, $allowed, true)) {
                    $validator->errors()->add("answers.{$i}.value", "Invalid answer for '{$key}'.");
                }

                $answered[$key] = true;
            }

            foreach ($questions as $key => $question) {
                if (! isset($answered[$key])) {
                    $validator->errors()->add('answers', "Missing answer for '{$key}'.");
                }
            }
        });
    }

    /**
     * The quiz questions in display order, keyed by slug. Read from the DB once
     * per request and cached so the controller/action don't query again.
     *
     * @return Collection<string, QuizQuestion>
     */
    public function questions(): Collection
    {
        return $this->questions ??= QuizQuestion::orderBy('position')->get()->keyBy('key');
    }
}
