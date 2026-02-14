<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AdminTransactionController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/user', [AuthController::class, 'user'])
    ->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/reset-password/{token}', function ($token) {
        return response()->json(['token' => $token]);
    })->name('password.reset');
});

Route::prefix('v1')->middleware(['verify.server.token', 'verify.api.key', 'verify.signature'])->group(function () {
    Route::post('/vend/electricity', [BillPaymentController::class, 'vendElectricity']);
    Route::post('/vend/entertainment', [BillPaymentController::class, 'vendEntertainment']);
    Route::post('/vend/telecoms', [BillPaymentController::class, 'vendTelecoms']);
    Route::get('/check/meter', [BillPaymentController::class, 'checkMeter']);
    Route::get('/transaction/{orderId}', [BillPaymentController::class, 'getTransaction']);
    // Admin Reporting
    Route::get('/admin/transactions', [AdminTransactionController::class, 'index']);
});

// Backwards compatibility: expose same endpoints without the /v1 prefix
Route::middleware(['verify.server.token', 'verify.api.key', 'verify.signature'])->group(function () {
    Route::post('/vend/electricity', [BillPaymentController::class, 'vendElectricity']);
    Route::post('/vend/entertainment', [BillPaymentController::class, 'vendEntertainment']);
    Route::post('/vend/telecoms', [BillPaymentController::class, 'vendTelecoms']);
    Route::get('/check/meter', [BillPaymentController::class, 'checkMeter']);
    Route::get('/transaction/{orderId}', [BillPaymentController::class, 'getTransaction']);
    Route::get('/admin/transactions', [AdminTransactionController::class, 'index']);
});

// v2 Admin Dashboard and Customer Portal Routes
Route::prefix('v2')->middleware(['verify.server.token', 'verify.api.key', 'verify.signature'])->group(function () {
    // Admin Dashboard Only
    Route::get('/wallet/balance', [AdminController::class, 'walletBalance']);
    Route::get('/transactions', [AdminTransactionController::class, 'transactions']);

    // Both Admin Dashboard and Customer Portal
    Route::get('/discos/status', [AdminController::class, 'discosStatus']);
});

