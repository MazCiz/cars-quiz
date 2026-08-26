<?php

namespace App\Services;

use App\Models\QuizQuestion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    /**
     * Generate a personalized car recommendation from the user's quiz answers.
     *
     * Uses the Claude API when configured; falls back to a deterministic
     * rule-based recommendation so the quiz still works without an API key.
     *
     * @param  array<string,string>  $answers  keyed by question slug
     * @return array{text: string, source: string}
     */
    public function generate(array $answers): array
    {
        // Human-readable "Question: Answer" lines, in question order.
        $lines = $this->describeAnswers($answers);

        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            return ['text' => $this->fallback($answers), 'source' => 'fallback'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-opus-4-8'),
                'max_tokens' => 700,
                'system' => "You are a friendly, knowledgeable car-buying advisor. Given a person's quiz answers, recommend the kind of car that suits them: the body style / segment, a fuel type, transmission, and 1–2 example models to look at, with a short warm explanation grounded in their specific answers. Write ~120–170 words, in the second person. Do not invent prices or fabricate statistics.",
                'messages' => [[
                    'role' => 'user',
                    'content' => "Here are my answers to a car-finder quiz:\n\n".implode("\n", $lines)."\n\nWhat kind of car should I look for, and why?",
                ]],
            ]);

            if ($response->successful()) {
                $text = collect($response->json('content', []))
                    ->firstWhere('type', 'text')['text'] ?? null;
                if (! empty($text)) {
                    return ['text' => trim($text), 'source' => 'ai'];
                }
            }

            Log::warning('Claude recommendation failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::warning('Claude recommendation error', ['message' => $e->getMessage()]);
        }

        return ['text' => $this->fallback($answers), 'source' => 'fallback'];
    }

    /**
     * Turn slug answers into readable "Question — Answer label" lines using the
     * seeded questions, so the AI sees natural language rather than slugs.
     *
     * @param  array<string,string>  $answers
     * @return array<int,string>
     */
    private function describeAnswers(array $answers): array
    {
        $questions = QuizQuestion::orderBy('position')->get();
        $lines = [];

        foreach ($questions as $q) {
            $value = $answers[$q->key] ?? null;
            if ($value === null) {
                continue;
            }
            $label = collect($q->options)->firstWhere('value', $value)['label'] ?? $value;
            $lines[] = "- {$q->text} {$label}";
        }

        return $lines;
    }

    /**
     * Rule-based recommendation used when the AI isn't configured or fails.
     * Deliberately simple — enough to be useful and honest.
     *
     * @param  array<string,string>  $answers
     */
    private function fallback(array $answers): string
    {
        $bodyByUsage = [
            'family' => 'a practical estate or compact SUV',
            'adventure' => 'a capable SUV or crossover with all-wheel drive',
            'business' => 'a comfortable saloon or executive hatchback',
            'leisure' => 'a versatile crossover',
            'commute' => 'an efficient hatchback',
        ];
        $body = $bodyByUsage[$answers['usage'] ?? ''] ?? 'a well-rounded hatchback';

        if (($answers['passengers'] ?? '') === 'large_family' || ($answers['cargo'] ?? '') === 'towing') {
            $body = 'a seven-seat SUV or large estate';
        }

        $fuelByMileage = [
            'high' => 'a diesel or hybrid keeps running costs down at high mileage',
            'low' => 'petrol or electric suits your lower yearly distance',
            'medium' => 'a hybrid is a balanced choice for your mileage',
        ];
        $fuelHint = $fuelByMileage[$answers['mileage'] ?? ''] ?? 'choose a fuel type that fits your typical journeys';
        if (($answers['fuel'] ?? 'no_pref') !== 'no_pref') {
            $fuelHint = "you preferred {$answers['fuel']}, which fits well here";
        }

        $priorityNote = [
            'economy' => 'Prioritise low running costs and good fuel economy.',
            'reliability' => 'Look for models with a strong reliability record.',
            'performance' => 'Lean towards a livelier engine and sharper handling.',
            'comfort' => 'Favour a refined, well-equipped, comfortable cabin.',
            'safety' => 'Check for a strong safety rating and driver-assist features.',
        ][$answers['priority'] ?? ''] ?? 'Balance running costs against comfort and features.';

        return "Based on your answers, look for {$body}. On fuel, {$fuelHint}. {$priorityNote} ".
            "Take a few candidates for a test drive and compare running costs before deciding. ".
            "(This is a rule-based suggestion — add an Anthropic API key to get a richer, AI-personalised recommendation.)";
    }
}
