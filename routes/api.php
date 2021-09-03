<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
<<<<<<< HEAD
=======
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\PaymentMethodController;
>>>>>>> develop
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CurrencyController;
<<<<<<< HEAD
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProviderCategoryController;

=======
>>>>>>> develop

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware([])->group(function () {
    Route::prefix('cost-center')->group(function () {
        Route::get('/', [CostCenterController::class, 'index']);
        Route::get('/{id}', [CostCenterController::class, 'show']);
        Route::post('/', [CostCenterController::class, 'store']);
        Route::put('/{id}', [CostCenterController::class, 'update']);
        Route::delete('/{id}', [CostCenterController::class, 'destroy']);
    });
});

Route::middleware([])->group(function () {
    Route::prefix('currency')->group(function () {
        Route::get('/', [CurrencyController::class, 'index']);
        Route::get('/{id}', [CurrencyController::class, 'show']);
        Route::post('/', [CurrencyController::class, 'store']);
        Route::put('/{id}', [CurrencyController::class, 'update']);
        Route::delete('/{id}', [CurrencyController::class, 'destroy']);
    });
});

Route::middleware([])->group(function () {
    Route::prefix('payment-method')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::get('/{id}', [PaymentMethodController::class, 'show']);
        Route::post('/', [PaymentMethodController::class, 'store']);
        Route::put('/{id}', [PaymentMethodController::class, 'update']);
        Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
    });
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
<<<<<<< HEAD

//Restful route -> Provider Categories
Route::middleware([])->group(function () {
    Route::prefix('provider-category')->group(function () {
        Route::get('/', [ProviderCategoryController::class, 'index']);
        Route::get('/{id}', [ProviderCategoryController::class, 'show']);
        Route::post('/', [ProviderCategoryController::class, 'store']);
        Route::put('/{id}', [ProviderCategoryController::class, 'update']);
        Route::delete('/{id}', [ProviderCategoryController::class, 'destroy']);
    });
});
=======
>>>>>>> develop
