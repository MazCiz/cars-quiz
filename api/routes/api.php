<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Health check — confirms the API is up and can reach Postgres. The Next.js
// frontend calls this to verify the full stack is wired together.
Route::get('/ping', function () {
    $db = 'ok';
    try {
        DB::connection()->getPdo();
    } catch (\Throwable $e) {
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
