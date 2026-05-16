<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VideoProjectController;
use App\Http\Controllers\Api\TranscribeController;

Route::middleware('web')->group(function () {
    Route::get('/video-projects', [VideoProjectController::class, 'index']);
});

// No web middleware — CSRF not needed for multipart API calls from frontend
Route::post('/transcribe', TranscribeController::class);
