<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\BankController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//Restful route -> Payments Types
Route::get('payment-types', [PaymentTypeController::class, 'index']);
Route::post('payment-type', [PaymentTypeController::class, 'store']);
Route::put('payment-type/{id}', [PaymentTypeController::class, 'update']);
Route::delete('payment-type/{id}', [PaymentTypeController::class, 'destroy']);

//Restful route -> Banks
Route::get('banks', [BankController::class, 'index']);
Route::post('bank', [BankController::class, 'store']);
Route::put('bank/{id}', [BankController::class, 'update']);
Route::delete('bank/{id}', [BankController::class, 'destroy']);

