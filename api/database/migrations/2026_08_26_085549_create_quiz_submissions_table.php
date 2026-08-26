<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_submissions', function (Blueprint $table) {
            $table->id();
            // The user's answers keyed by question slug: { "budget": "10_20k", ... }
            $table->json('answers');
            // The AI-generated recommendation text (null until generated / if it fails).
            $table->text('recommendation')->nullable();
            // Where the recommendation came from: "ai" or "fallback".
            $table->string('recommendation_source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_submissions');
    }
};
