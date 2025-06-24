<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\Api\AI\TextToImageController;
use App\Http\Controllers\Api\AI\BackgroundRemoverController;
use App\Http\Controllers\Api\AI\TextToSpeechController;
use App\Http\Controllers\Api\AI\VoiceToTextController;
use App\Http\Controllers\Api\AI\TextToVideoController;
use App\Http\Controllers\Api\AI\AutoLayoutController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Hello World'
    ]);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::put('/user', [AuthController::class, 'updateUser']);
});

// AI Services - Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('text-to-image', [TextToImageController::class, 'Imagegenerate']);
    Route::post('background-remover', [BackgroundRemoverController::class, 'Backgroundremove']);
    Route::post('text-to-speech', [TextToSpeechController::class, 'Speechgenerate']);
    Route::post('voice-to-text', [VoiceToTextController::class, 'transcribe']);
    Route::post('text-to-video', [TextToVideoController::class, 'Videogenerate']);
    Route::post('auto-layout', [AutoLayoutController::class, 'suggest']);
});

// Handle authentication failures for API routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found'
    ], 404);
});
