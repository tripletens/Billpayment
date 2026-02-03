<?php

use App\Http\Controllers\API\ElectricityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\API\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
    Route::post('/forgot-password', [\App\Http\Controllers\API\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\API\AuthController::class, 'resetPassword']);
    Route::get('/reset-password/{token}', function ($token) {
        return response()->json(['token' => $token]);
    })->name('password.reset');
});

Route::middleware(['verify.api.key', 'verify.signature'])->group(function () {
    Route::post('/vend/electricity', [ElectricityController::class, 'vend']);
});
