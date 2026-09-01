<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a user-submitted question idea (POST /quiz/suggestions).
 */
class SuggestQuestionRequest extends FormRequest
{
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
            'text' => ['required', 'string', 'min:5', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
