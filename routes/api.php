<?php

use App\Http\Controllers\API\ElectricityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['verify.api.key', 'verify.signature'])->group(function () {
    Route::post('/vend/electricity', [ElectricityController::class, 'vend']);
});
