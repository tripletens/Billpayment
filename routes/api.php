<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/user', [\App\Http\Controllers\API\AuthController::class, 'user'])
    ->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\API\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
    Route::post('/forgot-password', [\App\Http\Controllers\API\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\API\AuthController::class, 'resetPassword']);
    Route::get('/reset-password/{token}', function ($token) {
        return response()->json(['token' => $token]);
    })->name('password.reset');
});

Route::middleware(['verify.server.token', 'verify.api.key', 'verify.signature'])->group(function () {
    Route::post('/vend/electricity', [\App\Http\Controllers\API\BillPaymentController::class, 'vendElectricity']);
    Route::post('/vend/entertainment', [\App\Http\Controllers\API\BillPaymentController::class, 'vendEntertainment']);
    Route::post('/vend/telecoms', [\App\Http\Controllers\API\BillPaymentController::class, 'vendTelecoms']);
    // Admin Reporting
    Route::get('/admin/transactions', [\App\Http\Controllers\API\AdminTransactionController::class, 'index']);
});
