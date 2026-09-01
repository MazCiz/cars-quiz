<?php

namespace Tests\Feature;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionSuggestion;
use Database\Seeders\QuizQuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuizQuestionSeeder::class);
    }

    /**
     * A complete, valid submission: the first option of every question.
     *
     * @return array<int, array{key: string, value: string, time_spent_ms: int}>
     */
    private function validAnswers(): array
    {
        return QuizQuestion::orderBy('position')->get()
            ->map(fn ($q) => [
                'key' => $q->key,
                'value' => $q->options[0]['value'],
                'time_spent_ms' => 1000,
            ])
            ->all();
    }

    public function test_questions_endpoint_returns_all_seeded_questions(): void
    {
        $this->getJson('/api/quiz/questions')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data' => [['key', 'text', 'options']]]);
    }

    public function test_submit_stores_the_attempt_and_returns_a_recommendation(): void
    {
        $response = $this->postJson('/api/quiz/submit', ['answers' => $this->validAnswers()]);

        // A resource wrapping a freshly-created attempt returns 201 Created.
        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'recommendation', 'source', 'total_time_ms']]);

        // No API key in the testing env → deterministic rule-based fallback.
        $this->assertSame('fallback', $response->json('data.source'));
        $this->assertSame(10_000, $response->json('data.total_time_ms'));

        $this->assertDatabaseCount(QuizAttempt::class, 1);
        $this->assertDatabaseCount(QuizAnswer::class, 10);
    }

    public function test_submit_rejects_a_duplicated_question(): void
    {
        $answers = $this->validAnswers();
        $answers[] = $answers[0]; // same question twice

        $this->postJson('/api/quiz/submit', ['answers' => $answers])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.10.key']);

        $this->assertDatabaseCount(QuizAttempt::class, 0);
    }

    public function test_submit_rejects_a_value_that_is_not_an_option(): void
    {
        $answers = $this->validAnswers();
        $answers[0]['value'] = 'not-a-real-option';

        $this->postJson('/api/quiz/submit', ['answers' => $answers])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.0.value']);
    }

    public function test_submit_rejects_a_missing_question(): void
    {
        $answers = $this->validAnswers();
        array_pop($answers); // drop the last question

        $this->postJson('/api/quiz/submit', ['answers' => $answers])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    public function test_suggestions_are_stored(): void
    {
        $this->postJson('/api/quiz/suggestions', [
            'text' => 'How important is towing capacity to you?',
            'email' => 'someone@example.com',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id']]);

        $this->assertDatabaseCount(QuizQuestionSuggestion::class, 1);
    }

    public function test_short_suggestions_are_rejected(): void
    {
        $this->postJson('/api/quiz/suggestions', ['text' => 'hi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }
}
