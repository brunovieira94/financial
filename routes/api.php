<?php

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
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\ApprovalFlowByUserController;
use App\Http\Controllers\TypeOfTaxController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ItauCNABController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\MeasurementUnitController;
use App\Http\Controllers\AttributeTypeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ApprovalFlowSupplyController;
use App\Http\Controllers\ApprovalFlowSupplyByUserController;
use App\Http\Controllers\ReasonToRejectController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\HotelApprovalFlowController;

Route::middleware(['auth:api', 'check.permission'])->group(function () {

    Route::prefix('cost-center')->group(function () {
        Route::get('/', [CostCenterController::class, 'index']);
        Route::get('filter-user/', [CostCenterController::class, 'costCenterFilterUser']);
        Route::get('/all', [CostCenterController::class, 'allCostCenters']);
        Route::get('/{id}', [CostCenterController::class, 'show']);
        Route::post('/', [CostCenterController::class, 'store']);
        Route::put('/{id}', [CostCenterController::class, 'update']);
        Route::delete('/{id}', [CostCenterController::class, 'destroy']);
        Route::post('/import', [CostCenterController::class, 'import']);
        Route::post('/export', [CostCenterController::class, 'export']);
    });

    Route::prefix('currency')->group(function () {
        Route::get('/', [CurrencyController::class, 'index']);
        Route::get('/{id}', [CurrencyController::class, 'show']);
        Route::post('/', [CurrencyController::class, 'store']);
        Route::put('/{id}', [CurrencyController::class, 'update']);
        Route::delete('/{id}', [CurrencyController::class, 'destroy']);
        Route::post('/import', [CurrencyController::class, 'import']);
    });

    Route::prefix('payment-method')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::get('/{id}', [PaymentMethodController::class, 'show']);
        Route::post('/', [PaymentMethodController::class, 'store']);
        Route::put('/{id}', [PaymentMethodController::class, 'update']);
        Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
        Route::post('/import', [PaymentMethodController::class, 'import']);
    });

    //Restful route -> Payments Types
    Route::prefix('payment-type')->group(function () {
        Route::get('/', [PaymentTypeController::class, 'index']);
        Route::get('/{id}', [PaymentTypeController::class, 'show']);
        Route::post('/', [PaymentTypeController::class, 'store']);
        Route::put('/{id}', [PaymentTypeController::class, 'update']);
        Route::delete('/{id}', [PaymentTypeController::class, 'destroy']);
        Route::post('/import', [PaymentTypeController::class, 'import']);
    });


    //Restful route -> Banks
    Route::prefix('bank')->group(function () {
        Route::get('/', [BankController::class, 'index']);
        Route::get('/{id}', [BankController::class, 'show']);
        Route::post('/', [BankController::class, 'store']);
        Route::put('/{id}', [BankController::class, 'update']);
        Route::delete('/{id}', [BankController::class, 'destroy']);
        Route::post('/import', [BankController::class, 'import']);
    });

    Route::prefix('chart-of-accounts')->group(function () {
        Route::get('/', [ChartOfAccountsController::class, 'index']);
        Route::get('/all', [ChartOfAccountsController::class, 'allChartOfAccounts']);
        Route::get('/{id}', [ChartOfAccountsController::class, 'show']);
        Route::post('/', [ChartOfAccountsController::class, 'store']);
        Route::put('/{id}', [ChartOfAccountsController::class, 'update']);
        Route::delete('/{id}', [ChartOfAccountsController::class, 'destroy']);
        Route::post('/import', [ChartOfAccountsController::class, 'import']);
        Route::post('/export', [ChartOfAccountsController::class, 'export']);
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
        Route::post('/import', [ProviderCategoryController::class, 'import']);
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
        Route::post('/import', [StateController::class, 'import']);
    });
    //Restful route -> City
    Route::prefix('city')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/{id}', [CityController::class, 'show']);
        Route::post('/', [CityController::class, 'store']);
        Route::put('/{id}', [CityController::class, 'update']);
        Route::delete('/{id}', [CityController::class, 'destroy']);
        Route::post('/import', [CityController::class, 'import']);
    });

    Route::prefix('approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowController::class, 'index']);
        Route::post('/', [ApprovalFlowController::class, 'store']);
        Route::get('/all', [ApprovalFlowController::class, 'index']);
    });
    //Restful route -> Provider
    Route::prefix('provider')->group(function () {
        Route::get('/', [ProviderController::class, 'index']);
        Route::get('/{id}', [ProviderController::class, 'show']);
        Route::post('/', [ProviderController::class, 'store']);
        Route::put('/{id}', [ProviderController::class, 'update']);
        Route::delete('/{id}', [ProviderController::class, 'destroy']);
        Route::post('/import', [ProviderController::class, 'import']);
        Route::post('/export', [ProviderController::class, 'export']);
    });

    Route::prefix('hotel')->group(function () {
        Route::get('/', [HotelController::class, 'index']);
        Route::get('/{id}', [HotelController::class, 'show']);
        Route::post('/', [HotelController::class, 'store']);
        Route::put('/{id}', [HotelController::class, 'update']);
        Route::delete('/{id}', [HotelController::class, 'destroy']);
        Route::post('/import', [HotelController::class, 'import']);
        Route::post('/export', [HotelController::class, 'export']);
    });

    Route::prefix('billing')->group(function () {
        Route::get('/', [BillingController::class, 'index']);
        Route::post('cangooroo', [BillingController::class, 'getCangoorooData']);
        Route::get('/{id}', [BillingController::class, 'show']);
        Route::post('/', [BillingController::class, 'store']);
        Route::put('/{id}', [BillingController::class, 'update']);
        Route::put('/approve/{id}', [BillingController::class, 'approve']);
        Route::put('/reprove/{id}', [BillingController::class, 'reprove']);
        Route::delete('/{id}', [BillingController::class, 'destroy']);
        Route::post('/export', [BillingController::class, 'export']);
    });

    Route::prefix('hotel-approval-flow')->group(function () {
        Route::get('/', [HotelApprovalFlowController::class, 'index']);
        Route::post('/', [HotelApprovalFlowController::class, 'store']);
        Route::get('/all', [HotelApprovalFlowController::class, 'index']);
    });

    //Restful route -> Company
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::put('/{id}', [CompanyController::class, 'update']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
        Route::post('/import', [CompanyController::class, 'import']);
    });

    //Restful route -> Business
    Route::prefix('business')->group(function () {
        Route::get('/', [BusinessController::class, 'index']);
        Route::get('/{id}', [BusinessController::class, 'show']);
        Route::post('/', [BusinessController::class, 'store']);
        Route::put('/{id}', [BusinessController::class, 'update']);
        Route::delete('/{id}', [BusinessController::class, 'destroy']);
        Route::post('/import', [BusinessController::class, 'import']);
    });

    //Restful route -> User
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/import', [UserController::class, '']);
        Route::post('/export', [UserController::class, 'export']);
    });

    Route::put('update-user/', [UserController::class, 'updateMyUser']);


    Route::prefix('logs')->group(function () {
        Route::get('/', [LogsController::class, 'index']);
        Route::get('/log-payment-request/{id}', [LogsController::class, 'getPaymentRequestLogs']);
        Route::get('/log-purchase-order/{id}', [LogsController::class, 'getPurchaseOrderLogs']);
        Route::get('/{log_name}/{subject_id}', [LogsController::class, 'getLogs']);
    });

    Route::prefix('country')->group(function () {
        Route::get('/', [CountryController::class, 'index']);
        Route::get('/{id}', [CountryController::class, 'show']);
        Route::post('/', [CountryController::class, 'store']);
        Route::put('/{id}', [CountryController::class, 'update']);
        Route::delete('/{id}', [CountryController::class, 'destroy']);
        Route::post('/import', [CountryController::class, 'import']);
    });

    //Restful route -> Payment Request
    Route::prefix('payment-request')->group(function () {
        Route::get('/all', [PaymentRequestController::class, 'getAllPaymentRequest']);
        Route::post('/import', [PaymentRequestController::class, 'import']);
        Route::get('/group-form-payment', [PaymentRequestController::class, 'groupFormPayment']);
        Route::get('/', [PaymentRequestController::class, 'index']);
        Route::get('/{id}', [PaymentRequestController::class, 'show']);
        Route::post('/', [PaymentRequestController::class, 'store'])->middleware(['check.installments', 'check.values.invoice']);
        Route::post('/{id}', [PaymentRequestController::class, 'update'])->middleware(['check.installments', 'check.values.invoice']);
        Route::post('update-installment/{id}', [PaymentRequestController::class, 'updateInstallment']);
        Route::delete('/{id}', [PaymentRequestController::class, 'destroy']);
    });

    Route::put('/update-date-installment', [PaymentRequestController::class, 'updateDateInstallment']);

    Route::prefix('account-payable-approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowByUserController::class, 'accountsApproveUser']);
        Route::put('/approve-many', [ApprovalFlowByUserController::class, 'approveManyAccounts']);
        Route::put('/approve/{id}', [ApprovalFlowByUserController::class, 'approveAccount']);
        Route::put('/reprove/{id}', [ApprovalFlowByUserController::class, 'reproveAccount']);
        Route::put('/cancel/{id}', [ApprovalFlowByUserController::class, 'cancelAccount']);
        Route::post('/export', [ApprovalFlowByUserController::class, 'accountsApproveUserExport']);
    });

    Route::prefix('type-of-tax')->group(function () {
        Route::get('/', [TypeOfTaxController::class, 'index']);
        Route::get('/{id}', [TypeOfTaxController::class, 'show']);
        Route::post('/', [TypeOfTaxController::class, 'store']);
        Route::put('/{id}', [TypeOfTaxController::class, 'update']);
        Route::delete('/{id}', [TypeOfTaxController::class, 'destroy']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/due-bills', [ReportController::class, 'duePaymentRequest']);
        Route::get('/due-installments', [ReportController::class, 'dueInstallment']);
        Route::get('/approved-payment-request', [ReportController::class, 'approvedPaymentRequest']);
        Route::get('/approved-installment', [ReportController::class, 'approvedInstallment']);
        Route::get('/disapproved-payment-request', [ReportController::class, 'disapprovedPaymentRequest']);
        Route::get('/payment-requests-deleted', [ReportController::class, 'paymentRequestsDeleted']);
        Route::get('/bills-to-pay', [ReportController::class, 'billsToPay']);
        Route::get('/installments-payable', [ReportController::class, 'installmentsPayable']);
        Route::get('/payment-requests-cnab-generated', [ReportController::class, 'generatedCNABPaymentRequestCNAB']);
        Route::get('/payment-requests-paid', [ReportController::class, 'paymentRequestPaid']);
        Route::get('/payment-requests-finished', [ReportController::class, 'paymentRequestFinished']);
        Route::get('/approved-purchase-order', [ReportController::class, 'approvedPurchaseOrder']);
        Route::get('/payment-requests-cnab-generated-list', [ReportController::class, 'getAllCnabGenerate']);
        Route::get('/payment-requests-cnab-generated-list/{id}', [ReportController::class, 'getCnabGenerate']);
        Route::post('/due-bills/export', [ReportController::class, 'duePaymentRequestExport']);
        Route::post('/approved-payment-request/export', [ReportController::class, 'approvedPaymentRequestExport']);
        Route::post('/disapproved-payment-request/export', [ReportController::class, 'disapprovedPaymentRequestExport']);
        Route::post('/payment-requests-deleted/export', [ReportController::class, 'paymentRequestsDeletedExport']);
        Route::post('/payment-requests-cnab-generated/export', [ReportController::class, 'generatedCNABPaymentRequestCNABExport']);
        Route::post('/bills-to-pay/export', [ReportController::class, 'billsToPayExport']);
        Route::post('/payment-requests-paid/export', [ReportController::class, 'paymentRequestPaidExport']);
        Route::post('/payment-requests-finished/export', [ReportController::class, 'paymentRequestFinishedExport']);
        Route::post('/approved-installment/export', [ReportController::class, 'approvedInstallmentExport']);
        Route::post('/installments-payable/export', [ReportController::class, 'installmentsPayableExport']);
        Route::post('/due-installments/export', [ReportController::class, 'dueInstallmentsExport']);
    });

    Route::prefix('product')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/import', [ProductController::class, 'import']);
    });

    Route::prefix('service')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('/{id}', [ServiceController::class, 'show']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{id}', [ServiceController::class, 'update']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
        Route::post('/import', [ServiceController::class, 'import']);
    });

    Route::prefix('measurement-unit')->group(function () {
        Route::get('/', [MeasurementUnitController::class, 'index']);
        Route::get('/{id}', [MeasurementUnitController::class, 'show']);
        Route::post('/', [MeasurementUnitController::class, 'store']);
        Route::put('/{id}', [MeasurementUnitController::class, 'update']);
        Route::delete('/{id}', [MeasurementUnitController::class, 'destroy']);
        Route::post('/import', [MeasurementUnitController::class, 'import']);
    });

    Route::prefix('attribute-type')->group(function () {
        Route::get('/', [AttributeTypeController::class, 'index']);
        Route::get('/{id}', [AttributeTypeController::class, 'show']);
        Route::post('/', [AttributeTypeController::class, 'store']);
        Route::put('/{id}', [AttributeTypeController::class, 'update']);
        Route::delete('/{id}', [AttributeTypeController::class, 'destroy']);
        Route::post('/import', [AttributeTypeController::class, 'import']);
    });

    Route::prefix('cnab')->group(function () {
        Route::prefix('/240')->group(function () {
            Route::post('/shipping', [ItauCNABController::class, 'shipping240']);
            Route::post('/return', [ItauCNABController::class, 'return240']);
            Route::post('/cnab-parse', [ItauCNABController::class, 'cnabParse']);
        });
    });

    Route::prefix('purchase-order')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::post('/{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
    });

    Route::prefix('purchase-request')->group(function () {
        Route::get('/', [PurchaseRequestController::class, 'index']);
        Route::get('/{id}', [PurchaseRequestController::class, 'show']);
        Route::post('/', [PurchaseRequestController::class, 'store']);
        Route::post('/{id}', [PurchaseRequestController::class, 'update']);
        Route::delete('/{id}', [PurchaseRequestController::class, 'destroy']);
    });

    Route::prefix('approval-flow-supply')->group(function () {
        Route::get('/', [ApprovalFlowSupplyController::class, 'index']);
        Route::post('/', [ApprovalFlowSupplyController::class, 'store']);
        Route::get('/all', [ApprovalFlowSupplyController::class, 'index']);
    });

    Route::prefix('supply-approval-flow')->group(function () {
        Route::get('/', [ApprovalFlowSupplyByUserController::class, 'accountsApproveUser']);
        Route::put('/approve/{id}', [ApprovalFlowSupplyByUserController::class, 'approveAccount']);
        Route::put('/approve-many', [ApprovalFlowSupplyByUserController::class, 'approveManyAccounts']);
        Route::put('/reprove/{id}', [ApprovalFlowSupplyByUserController::class, 'reproveAccount']);
        Route::put('/cancel/{id}', [ApprovalFlowSupplyByUserController::class, 'cancelAccount']);
    });

    Route::prefix('reason-to-reject')->group(function () {
        Route::get('/', [ReasonToRejectController::class, 'index']);
        Route::get('/{id}', [ReasonToRejectController::class, 'show']);
        Route::post('/', [ReasonToRejectController::class, 'store']);
        Route::put('/{id}', [ReasonToRejectController::class, 'update']);
        Route::delete('/{id}', [ReasonToRejectController::class, 'destroy']);
    });
});

//Restful route -> Login
Route::prefix('/auth')->group(function () {
    Route::post('/', [AuthController::class, 'login']);
});
