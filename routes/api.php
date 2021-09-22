<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\ChartOfAccountsController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProviderCategoryController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LogsController;

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

Route::middleware([])->group(function () {
    Route::prefix('chart-of-accounts')->group(function () {
        Route::get('/', [ChartOfAccountsController::class, 'index']);
        Route::get('/{id}', [ChartOfAccountsController::class, 'show']);
        Route::post('/', [ChartOfAccountsController::class, 'store']);
        Route::put('/{id}', [ChartOfAccountsController::class, 'update']);
        Route::delete('/{id}', [ChartOfAccountsController::class, 'destroy']);
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

Route::middleware([])->group(function () {
    Route::prefix('module')->group(function () {
        Route::get('/', [ModuleController::class, 'index']);
        Route::get('/{id}', [ModuleController::class, 'show']);
    });
});

Route::middleware([])->group(function () {
    Route::prefix('role')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });
});
//Restful route -> States
Route::middleware([])->group(function () {
    Route::prefix('state')->group(function () {
        Route::get('/', [StateController::class, 'index']);
        Route::get('/{id}', [StateController::class, 'show']);
        Route::post('/', [StateController::class, 'store']);
        Route::put('/{id}', [StateController::class, 'update']);
        Route::delete('/{id}', [StateController::class, 'destroy']);
    });
});

//Restful route -> City
Route::middleware([])->group(function () {
    Route::prefix('city')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/{id}', [CityController::class, 'show']);
        Route::post('/', [CityController::class, 'store']);
        Route::put('/{id}', [CityController::class, 'update']);
        Route::delete('/{id}', [CityController::class, 'destroy']);
    });
});

Route::middleware([])->group(function () {
    Route::prefix('approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowController::class, 'index']);
        Route::post('/', [ApprovalFlowController::class, 'store']);
    });
});

//Restful route -> Provider
Route::middleware([])->group(function () {
    Route::prefix('provider')->group(function () {
        Route::get('/', [ProviderController::class, 'index']);
        Route::get('/{id}', [ProviderController::class, 'show']);
        Route::post('/', [ProviderController::class, 'store']);
        Route::put('/{id}', [ProviderController::class, 'update']);
        Route::delete('/{id}', [ProviderController::class, 'destroy']);
    });
});

//Restful route -> Company
Route::middleware([])->group(function () {
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::put('/{id}', [CompanyController::class, 'update']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
    });
});

Route::middleware([])->group(function () {
    Route::prefix('logs')->group(function () {
        Route::get('/', [LogsController::class, 'index']);
        Route::get('/{log_name}/{subject_id}', [LogsController::class, 'getLogs']);
    });
});
