<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User-submitted question ideas. Intentionally NOT part of quiz_questions
        // — these are just captured for review, never shown in the live quiz.
        Schema::create('quiz_question_suggestions', function (Blueprint $table) {
            $table->id();
            $table->text('text');            // the suggested question
            $table->string('email')->nullable(); // optional, so we can follow up
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_question_suggestions');
    }
};
