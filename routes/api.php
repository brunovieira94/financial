<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTypeController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('paymentTypes', [PaymentTypeController::class, 'index']);
Route::post('paymentType', [PaymentTypeController::class, 'store']);


