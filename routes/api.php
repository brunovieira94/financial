<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\BankController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Restful route -> Payments Types
Route::get('payment-type', [PaymentTypeController::class, 'index']);
Route::get('payment-type/{id}', [PaymentTypeController::class, 'show']);
Route::post('payment-type', [PaymentTypeController::class, 'store']);
Route::put('payment-type/{id}', [PaymentTypeController::class, 'update']);
Route::delete('payment-type/{id}', [PaymentTypeController::class, 'destroy']);

//Restful route -> Banks
Route::get('bank', [BankController::class, 'index']);
Route::get('bank/{id}', [BankController::class, 'show']);
Route::post('bank', [BankController::class, 'store']);
Route::put('bank/{id}', [BankController::class, 'update']);
Route::delete('bank/{id}', [BankController::class, 'destroy']);

