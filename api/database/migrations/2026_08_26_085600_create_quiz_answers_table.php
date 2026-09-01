<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per answered question within an attempt, carrying the time the
        // user spent on that question in milliseconds.
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('quiz_attempts')
                ->cascadeOnDelete();
            $table->unsignedInteger('position');        // order answered, 1..N
            $table->string('question_key');             // slug of the question
            $table->string('value');                    // chosen option value
            $table->unsignedBigInteger('time_spent_ms'); // ms spent on this question
            $table->timestamps();

            $table->index(['attempt_id', 'position']);
            // One answer per question within an attempt — enforced in the schema
            // so a malformed payload can never record the same question twice.
            $table->unique(['attempt_id', 'question_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
