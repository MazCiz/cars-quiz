<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One completed quiz attempt / session. Its individual answers (with
        // per-answer timing) live in quiz_answers.
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            // Total time to complete, milliseconds (sum of per-answer times).
            $table->unsignedBigInteger('total_time_ms')->default(0);
            // AI-generated (or fallback) recommendation for this attempt.
            $table->text('recommendation')->nullable();
            $table->string('recommendation_source')->nullable(); // "ai" | "fallback"
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
