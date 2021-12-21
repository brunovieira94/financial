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
use App\Http\Controllers\UserController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\BillToPayController;
use App\Http\Controllers\ApprovalFlowByUserController;
use App\Http\Controllers\TypeOfTaxController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\MeasurementUnitController;
use App\Http\Controllers\AttributeTypeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ApprovalFlowSupplyController;
use App\Http\Controllers\ApprovalFlowSupplyByUserController;

Route::middleware(['auth:api', 'check.permission'])->group(function () {

    Route::prefix('cost-center')->group(function () {
        Route::get('/', [CostCenterController::class, 'index']);
        Route::get('/{id}', [CostCenterController::class, 'show']);
        Route::post('/', [CostCenterController::class, 'store']);
        Route::put('/{id}', [CostCenterController::class, 'update']);
        Route::delete('/{id}', [CostCenterController::class, 'destroy']);
    });

    Route::prefix('currency')->group(function () {
        Route::get('/', [CurrencyController::class, 'index']);
        Route::get('/{id}', [CurrencyController::class, 'show']);
        Route::post('/', [CurrencyController::class, 'store']);
        Route::put('/{id}', [CurrencyController::class, 'update']);
        Route::delete('/{id}', [CurrencyController::class, 'destroy']);
    });

    Route::prefix('payment-method')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::get('/{id}', [PaymentMethodController::class, 'show']);
        Route::post('/', [PaymentMethodController::class, 'store']);
        Route::put('/{id}', [PaymentMethodController::class, 'update']);
        Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
    });

//Restful route -> Payments Types
    Route::prefix('payment-type')->group(function () {
        Route::get('/', [PaymentTypeController::class, 'index']);
        Route::get('/{id}', [PaymentTypeController::class, 'show']);
        Route::post('/', [PaymentTypeController::class, 'store']);
        Route::put('/{id}', [PaymentTypeController::class, 'update']);
        Route::delete('/{id}', [PaymentTypeController::class, 'destroy']);
    });


//Restful route -> Banks
    Route::prefix('bank')->group(function () {
        Route::get('/', [BankController::class, 'index']);
        Route::get('/{id}', [BankController::class, 'show']);
        Route::post('/', [BankController::class, 'store']);
        Route::put('/{id}', [BankController::class, 'update']);
        Route::delete('/{id}', [BankController::class, 'destroy']);
    });

    Route::prefix('chart-of-accounts')->group(function () {
        Route::get('/', [ChartOfAccountsController::class, 'index']);
        Route::get('/{id}', [ChartOfAccountsController::class, 'show']);
        Route::post('/', [ChartOfAccountsController::class, 'store']);
        Route::put('/{id}', [ChartOfAccountsController::class, 'update']);
        Route::delete('/{id}', [ChartOfAccountsController::class, 'destroy']);
    });

//Restful route -> Bank Accounts
    Route::prefix('bank-account')->group(function () {
        Route::get('/', [BankAccountController::class, 'index']);
        Route::get('/{id}', [BankAccountController::class, 'show']);
        Route::post('/', [BankAccountController::class, 'store']);
        Route::put('/{id}', [BankAccountController::class, 'update']);
        Route::delete('/{id}', [BankAccountController::class, 'destroy']);
    });

//Restful route -> Provider Categories

    Route::prefix('provider-category')->group(function () {
        Route::get('/', [ProviderCategoryController::class, 'index']);
        Route::get('/{id}', [ProviderCategoryController::class, 'show']);
        Route::post('/', [ProviderCategoryController::class, 'store']);
        Route::put('/{id}', [ProviderCategoryController::class, 'update']);
        Route::delete('/{id}', [ProviderCategoryController::class, 'destroy']);
    });

    Route::prefix('module')->group(function () {
        Route::get('/', [ModuleController::class, 'index']);
        Route::get('/{id}', [ModuleController::class, 'show']);
    });

    Route::prefix('role')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });
//Restful route -> States

    Route::prefix('state')->group(function () {
        Route::get('/', [StateController::class, 'index']);
        Route::get('/{id}', [StateController::class, 'show']);
        Route::post('/', [StateController::class, 'store']);
        Route::put('/{id}', [StateController::class, 'update']);
        Route::delete('/{id}', [StateController::class, 'destroy']);
    });

//Restful route -> City
    Route::prefix('city')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/{id}', [CityController::class, 'show']);
        Route::post('/', [CityController::class, 'store']);
        Route::put('/{id}', [CityController::class, 'update']);
        Route::delete('/{id}', [CityController::class, 'destroy']);
    });

    Route::prefix('approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowController::class, 'index']);
        Route::post('/', [ApprovalFlowController::class, 'store']);
    });

//Restful route -> Provider
    Route::prefix('provider')->group(function () {
        Route::get('/', [ProviderController::class, 'index']);
        Route::get('/{id}', [ProviderController::class, 'show']);
        Route::post('/', [ProviderController::class, 'store']);
        Route::put('/{id}', [ProviderController::class, 'update']);
        Route::delete('/{id}', [ProviderController::class, 'destroy']);
    });

//Restful route -> Company
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::put('/{id}', [CompanyController::class, 'update']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
    });

//Restful route -> Business
    Route::prefix('business')->group(function () {
        Route::get('/', [BusinessController::class, 'index']);
        Route::get('/{id}', [BusinessController::class, 'show']);
        Route::post('/', [BusinessController::class, 'store']);
        Route::put('/{id}', [BusinessController::class, 'update']);
        Route::delete('/{id}', [BusinessController::class, 'destroy']);
    });

//Restful route -> User
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::prefix('logs')->group(function () {
        Route::get('/', [LogsController::class, 'index']);
        Route::get('/{log_name}/{subject_id}', [LogsController::class, 'getLogs']);
    });

    Route::prefix('country')->group(function () {
        Route::get('/', [CountryController::class, 'index']);
        Route::get('/{id}', [CountryController::class, 'show']);
        Route::post('/', [CountryController::class, 'store']);
        Route::put('/{id}', [CountryController::class, 'update']);
        Route::delete('/{id}', [CountryController::class, 'destroy']);
    });

    //Restful route -> Bill-to-pay
    Route::prefix('bill-to-pay')->group(function () {
        Route::get('/', [BillToPayController::class, 'index']);
        Route::get('/{id}', [BillToPayController::class, 'show']);
        Route::post('/', [BillToPayController::class, 'store'])->middleware(['check.installments', 'check.values.invoice']);
        Route::post('/{id}', [BillToPayController::class, 'update'])->middleware(['check.installments', 'check.values.invoice']);
        Route::delete('/{id}', [BillToPayController::class, 'destroy']);
        Route::put('/installment/pay/{id}', [BillToPayController::class, 'payInstallment']);
    });

    Route::prefix('account-payable-approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowByUserController::class, 'accountsApproveUser']);
        Route::put('/approve/{id}', [ApprovalFlowByUserController::class, 'approveAccount']);
        Route::put('/reprove/{id}', [ApprovalFlowByUserController::class, 'reproveAccount']);
        Route::put('/cancel/{id}', [ApprovalFlowByUserController::class, 'cancelAccount']);
    });

    Route::prefix('type-of-tax')->group(function () {
        Route::get('/', [TypeOfTaxController::class, 'index']);
        Route::get('/{id}', [TypeOfTaxController::class, 'show']);
        Route::post('/', [TypeOfTaxController::class, 'store']);
        Route::put('/{id}', [TypeOfTaxController::class, 'update']);
        Route::delete('/{id}', [TypeOfTaxController::class, 'destroy']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/due-bills', [ReportController::class, 'dueBills']);
        Route::get('/approved-bills', [ReportController::class, 'approvedBills']);
    });

    Route::prefix('product')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    Route::prefix('service')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/{id}', [ServiceController::class, 'show']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{id}', [ServiceController::class, 'update']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
    });

    Route::prefix('measurement-unit')->group(function () {
        Route::get('/', [MeasurementUnitController::class, 'index']);
        Route::get('/{id}', [MeasurementUnitController::class, 'show']);
        Route::post('/', [MeasurementUnitController::class, 'store']);
        Route::put('/{id}', [MeasurementUnitController::class, 'update']);
        Route::delete('/{id}', [MeasurementUnitController::class, 'destroy']);
    });

    Route::prefix('attribute-type')->group(function () {
        Route::get('/', [AttributeTypeController::class, 'index']);
        Route::get('/{id}', [AttributeTypeController::class, 'show']);
        Route::post('/', [AttributeTypeController::class, 'store']);
        Route::put('/{id}', [AttributeTypeController::class, 'update']);
        Route::delete('/{id}', [AttributeTypeController::class, 'destroy']);
    });

    Route::prefix('purchase-order')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::post('/{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
    });

    Route::prefix('approval-flow-supply')->group(function () {
        Route::get('/', [ApprovalFlowSupplyController::class, 'index']);
        Route::post('/', [ApprovalFlowSupplyController::class, 'store']);
    });

    Route::prefix('supply-approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowSupplyByUserController::class, 'accountsApproveUser']);
        Route::put('/approve/{id}', [ApprovalFlowSupplyByUserController::class, 'approveAccount']);
        Route::put('/reprove/{id}', [ApprovalFlowSupplyByUserController::class, 'reproveAccount']);
        Route::put('/cancel/{id}', [ApprovalFlowSupplyByUserController::class, 'cancelAccount']);
    });
});

//Restful route -> Login
    Route::prefix('/auth')->group(function () {
        Route::post('/', [AuthController::class, 'login']);
    });


