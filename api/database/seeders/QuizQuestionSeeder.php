<?php

namespace Database\Seeders;

use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;

class QuizQuestionSeeder extends Seeder
{
    public function run(): void
    {
        // The 10 questions used to match a user to the kind of car that suits
        // them. Each option's `value` is the stable slug stored in a submission
        // and sent to the AI; `label` is what the user sees.
        $questions = [
            [
                'key' => 'budget',
                'text' => "What's your budget?",
                'options' => [
                    ['value' => 'under_10k', 'label' => 'Under €10,000'],
                    ['value' => '10_20k', 'label' => '€10,000 – €20,000'],
                    ['value' => '20_35k', 'label' => '€20,000 – €35,000'],
                    ['value' => 'over_35k', 'label' => 'Over €35,000'],
                ],
            ],
            [
                'key' => 'usage',
                'text' => 'What will you mainly use it for?',
                'options' => [
                    ['value' => 'commute', 'label' => 'Daily commuting'],
                    ['value' => 'family', 'label' => 'Family transport'],
                    ['value' => 'leisure', 'label' => 'Weekend trips & leisure'],
                    ['value' => 'business', 'label' => 'Business & client meetings'],
                    ['value' => 'adventure', 'label' => 'Off-road & adventure'],
                ],
            ],
            [
                'key' => 'passengers',
                'text' => 'How many people, typically?',
                'options' => [
                    ['value' => 'solo', 'label' => 'Just me'],
                    ['value' => 'couple', 'label' => 'Two'],
                    ['value' => 'small_family', 'label' => '3 – 4'],
                    ['value' => 'large_family', 'label' => '5 or more'],
                ],
            ],
            [
                'key' => 'driving_env',
                'text' => 'Where do you drive most?',
                'options' => [
                    ['value' => 'city', 'label' => 'City streets'],
                    ['value' => 'highway', 'label' => 'Highways / long distance'],
                    ['value' => 'mixed', 'label' => 'A mix of both'],
                    ['value' => 'rough', 'label' => 'Rural / rough roads'],
                ],
            ],
            [
                'key' => 'fuel',
                'text' => 'Any fuel preference?',
                'options' => [
                    ['value' => 'petrol', 'label' => 'Petrol'],
                    ['value' => 'diesel', 'label' => 'Diesel'],
                    ['value' => 'hybrid', 'label' => 'Hybrid'],
                    ['value' => 'electric', 'label' => 'Electric'],
                    ['value' => 'no_pref', 'label' => 'No preference'],
                ],
            ],
            [
                'key' => 'priority',
                'text' => 'What matters most to you?',
                'options' => [
                    ['value' => 'economy', 'label' => 'Low running costs'],
                    ['value' => 'reliability', 'label' => 'Reliability'],
                    ['value' => 'performance', 'label' => 'Performance & fun'],
                    ['value' => 'comfort', 'label' => 'Comfort & luxury'],
                    ['value' => 'safety', 'label' => 'Safety'],
                ],
            ],
            [
                'key' => 'transmission',
                'text' => 'Preferred transmission?',
                'options' => [
                    ['value' => 'manual', 'label' => 'Manual'],
                    ['value' => 'automatic', 'label' => 'Automatic'],
                    ['value' => 'no_pref', 'label' => 'No preference'],
                ],
            ],
            [
                'key' => 'mileage',
                'text' => 'How much do you drive a year?',
                'options' => [
                    ['value' => 'low', 'label' => 'Under 10,000 km'],
                    ['value' => 'medium', 'label' => '10,000 – 25,000 km'],
                    ['value' => 'high', 'label' => 'Over 25,000 km'],
                ],
            ],
            [
                'key' => 'cargo',
                'text' => "What are your cargo needs?",
                'options' => [
                    ['value' => 'minimal', 'label' => 'Minimal'],
                    ['value' => 'moderate', 'label' => 'Moderate (groceries, luggage)'],
                    ['value' => 'large', 'label' => 'Large (sports gear, a dog)'],
                    ['value' => 'towing', 'label' => 'I need to tow'],
                ],
            ],
            [
                'key' => 'tech',
                'text' => 'How important are tech & features?',
                'options' => [
                    ['value' => 'not', 'label' => 'Not important'],
                    ['value' => 'somewhat', 'label' => 'Somewhat'],
                    ['value' => 'very', 'label' => 'Very — I want the latest'],
                ],
            ],
        ];

        foreach ($questions as $i => $q) {
            QuizQuestion::updateOrCreate(
                ['key' => $q['key']],
                [
                    'position' => $i + 1,
                    'text' => $q['text'],
                    'options' => $q['options'],
                ]
            );
        }
    }
}
