<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTypeController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Restful route -> Payments Types
Route::get('payment-type', [PaymentTypeController::class, 'index']);
Route::get('payment-type/{id}', [PaymentTypeController::class, 'show']);
Route::post('payment-type', [PaymentTypeController::class, 'store']);
Route::put('payment-type/{id}', [PaymentTypeController::class, 'update']);
Route::delete('payment-type/{id}', [PaymentTypeController::class, 'destroy']);


