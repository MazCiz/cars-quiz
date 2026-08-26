<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position');   // display order, 1..10
            $table->string('key')->unique();       // stable slug, e.g. "budget"
            $table->string('text');                // the question shown to the user
            $table->json('options');               // [{ value, label }, ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
