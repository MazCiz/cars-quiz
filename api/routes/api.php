<?php

use App\Http\Controllers\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Cars Quiz — fetch the questions and submit answers for an AI recommendation.
Route::prefix('quiz')->group(function () {
    Route::get('/questions', [QuizController::class, 'questions']);
    Route::get('/metrics', [QuizController::class, 'metrics']);

    // Write endpoints are throttled to blunt abuse (the submit path also spends
    // an upstream AI call, so it's the one most worth protecting).
    Route::post('/submit', [QuizController::class, 'submit'])->middleware('throttle:20,1');
    Route::post('/suggestions', [QuizController::class, 'suggest'])->middleware('throttle:10,1');
});

// Health check for the Next.js landing page. Laravel's built-in health route
// (`/up`, configured in bootstrap/app.php) only confirms the app boots — this
// one additionally reports Postgres reachability, which the frontend surfaces.
Route::get('/ping', function () {
    $db = 'ok';
    try {
        DB::connection()->getPdo();
    } catch (Throwable $e) {
        $db = 'down';
    }

    return response()->json([
        'status' => $db === 'ok' ? 'ok' : 'degraded',
        'service' => 'cars-quiz-api',
        'database' => $db,
        'timestamp' => now()->toIso8601String(),
    ], $db === 'ok' ? 200 : 503);
});

// Authenticated user (Sanctum) — kept from the API scaffold as a starting point.
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
