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
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Api\Admin\TemplateController;
use App\Http\Controllers\Api\Admin\MediaLibrayController;

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

// Project routes (user)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/save-state', [ProjectController::class, 'saveState']);
    Route::get('/projects/{id}/history', [ProjectController::class, 'history']);
    Route::post('/projects/{id}/export', [ProjectController::class, 'export']);
    Route::post('/projects/{projectId}/restore-history/{historyId}', [ProjectController::class, 'restoreFromHistory']);
});

// Admin routes (should be protected by admin middleware in production)
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // Template management
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{id}', [TemplateController::class, 'show']);
    Route::put('/templates/{id}', [TemplateController::class, 'update']);
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);
    Route::post('/templates/{id}/toggle-active', [TemplateController::class, 'toggleActive']);

    // Media library management
    Route::get('/media', [MediaLibrayController::class, 'index']);
    Route::post('/media', [MediaLibrayController::class, 'store']);
    Route::get('/media/{id}', [MediaLibrayController::class, 'show']);
    Route::put('/media/{id}', [MediaLibrayController::class, 'update']);
    Route::delete('/media/{id}', [MediaLibrayController::class, 'destroy']);
    Route::post('/media/{id}/toggle-active', [MediaLibrayController::class, 'toggleActive']);
    Route::get('/media-categories', [MediaLibrayController::class, 'categories']);
    Route::get('/media-types', [MediaLibrayController::class, 'types']);
});

// Handle authentication failures for API routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found'
    ], 404);
});
