<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BankAccountController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Restful route -> Payments Types
Route::middleware([])->group(function () {
    Route::prefix('payment-type')->group(function () {
        Route::get('/', [PaymentTypeController::class, 'index']);
        Route::get('/{id}', [PaymentTypeController::class, 'show']);
        Route::post('/', [PaymentTypeController::class, 'store']);
        Route::put('/{id}', [PaymentTypeController::class, 'update']);
        Route::delete('/{id}', [PaymentTypeController::class, 'destroy']);
    });
});

//Restful route -> Banks
Route::middleware([])->group(function () {
    Route::prefix('bank')->group(function () {
        Route::get('/', [BankController::class, 'index']);
        Route::get('/{id}', [BankController::class, 'show']);
        Route::post('/', [BankController::class, 'store']);
        Route::put('/{id}', [BankController::class, 'update']);
        Route::delete('/{id}', [BankController::class, 'destroy']);
    });
});

//Restful route -> Bank Accounts
Route::middleware([])->group(function () {
    Route::prefix('bank-account')->group(function () {
        Route::get('/', [BankAccountController::class, 'index']);
        Route::get('/{id}', [BankAccountController::class, 'show']);
        Route::post('/', [BankAccountController::class, 'store']);
        Route::put('/{id}', [BankAccountController::class, 'update']);
        Route::delete('/{id}', [BankAccountController::class, 'destroy']);
    });
});
