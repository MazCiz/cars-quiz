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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
