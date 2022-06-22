<?php

namespace App\Http\Controllers;

use App\Http\Requests\PutInstallmentsRequest;
use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequestRequest;
use App\Services\PaymentRequestService as PaymentRequestService;
use App\Http\Requests\PutPaymentRequestRequest;
use App\Imports\PaymentRequestsImport;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PurchaseOrderHasInstallments;

class PaymentRequestController extends Controller
{
    private $paymentRequestService;
    private $paymentRequestImport;

    public function __construct(PaymentRequestService $paymentRequestService, PaymentRequestsImport $paymentRequestImport)
    {
        $this->paymentRequestService = $paymentRequestService;
        $this->paymentRequestImport = $paymentRequestImport;
    }

    public function index(Request $request)
    {
        return $this->paymentRequestService->getPaymentRequestByUser($request->all());
    }

    public function getAllPaymentRequest(Request $request)
    {
        return $this->paymentRequestService->getAllPaymentRequest($request->all());
    }

    public function show($id)
    {
        return $this->paymentRequestService->getPaymentRequest($id);
    }

    public function store(StorePaymentRequestRequest $request)
    {
        $requestInfo = $request->all();

        if (array_key_exists('invoice_number', $requestInfo)) {
            if (!self::checkInvoiceOrBilletProviderExists('invoice_number', $request->invoice_number, $requestInfo)) {
                return response()->json([
                    'erro' => 'O número de nota fiscal ou invoice já foi cadastrado para este fornecedor na conta ' .
                        PaymentRequest::with('provider')
                        ->where('invoice_number', $request->invoice_number)
                        ->whereRelation('provider', 'id', '=', $request->provider_id)->first()->id .
                        '.'
                ], 409);
            }
            if (!self::checkInvoiceOrBilletExists('invoice_number', $request->invoice_number, $requestInfo)) {
                if (!$requestInfo['force_registration']) {
                    return response()->json([
                        'erro' => 'O número de nota fiscal ou invoice já foi cadastrado no sistema em outro fornecedor na conta ' .
                            PaymentRequest::where('invoice_number', $request->invoice_number)
                            ->first()->id .
                            ', tem certeza que deseja cadastrar mesmo assim?'
                    ], 424);
                }
            }
        }

        foreach ($requestInfo['installments'] as $installment) {
            if (array_key_exists('bar_code', $installment) && $installment['bar_code'] != NULL) {
                if (!self::checkInvoiceOrBilletExists('bar_code', $installment['bar_code'], $requestInfo)) {
                    return response()->json([
                        'erro' => 'O código do boleto ou sistema na conta' .
                            PaymentRequest::with('installments')
                            ->whereRelation('installments', 'bar_code', '=', $installment['bar_code'])
                            ->first()->id .
                            ', para cadastrar essa conta deve ser apagada.'
                    ], 409);
                    break;
                }
            }
            if (array_key_exists('billet_number', $installment) && $installment['billet_number'] != NULL) {
                if (!self::checkInvoiceOrBilletProviderExists('billet_number', $installment['billet_number'], $requestInfo)) {
                    return response()->json([
                        'erro' => 'O número do boleto já foi cadastrado para este fornecedor na conta ' .
                            PaymentRequest::with(['provider', 'installments'])
                            ->whereRelation('installments', 'billet_number', '=', $installment['billet_number'])
                            ->whereRelation('provider', 'id', '=', $requestInfo['provider_id'])->first()->id .
                            '.'
                    ], 409);
                    break;
                }
                if (!self::checkInvoiceOrBilletExists('billet_number', $installment['billet_number'], $requestInfo)) {
                    if (!$requestInfo['force_registration']) {
                        return response()->json([
                            'erro' => 'O número do boleto já foi cadastrado no sistema em outro fornecedor na conta ' .
                                PaymentRequest::with('installments')
                                ->whereRelation('installments', 'billet_number', '=', $installment['billet_number'])
                                ->first()->id .
                                ', tem certeza que deseja cadastrar mesmo assim?'
                        ], 424);
                        break;
                    }
                }
            }
        }

        if (!self::checkInstallmentsPurchaseOrder($request->all())) {
            return response()->json([
                'erro' => 'É necessário que informa ao menos parcela para cada pedido de compra.'
            ], 422);
        }

        return $this->paymentRequestService->postPaymentRequest($request);
    }

    public function update(PutPaymentRequestRequest $request, $id)
    {
        $requestInfo = $request->all();
        $paymentRequest = PaymentRequest::with(['provider', 'installments'])->findOrFail($id);

        if (array_key_exists('invoice_number', $requestInfo)) {
            if ($paymentRequest->invoice_number != $requestInfo['invoice_number']) {
                if (array_key_exists('invoice_number', $requestInfo)) {
                    if (!self::checkInvoiceOrBilletProviderExists('invoice_number', $request->invoice_number, $requestInfo)) {
                        return response()->json([
                            'erro' => 'O número de nota fiscal ou invoice já foi cadastrado para este fornecedor na conta ' .
                                PaymentRequest::with('provider')
                                ->where('invoice_number', $request->invoice_number)
                                ->whereRelation('provider', 'id', '=', $request->provider_id)->first()->id .
                                '.'
                        ], 409);
                    }
                    if (!self::checkInvoiceOrBilletExists('invoice_number', $request->invoice_number, $requestInfo)) {
                        if (!$requestInfo['force_registration']) {
                            return response()->json([
                                'erro' => 'O número da nota fiscal ou invoice já foi cadastrado no sistema em outro fornecedor na conta ' .
                                    PaymentRequest::where('invoice_number', $request->invoice_number)
                                    ->first()->id .
                                    ', tem certeza que deseja cadastrar mesmo assim?'
                            ], 424);
                        }
                    }
                }
            }
        }

        foreach ($requestInfo['installments'] as $installment) {
            if (array_key_exists('bar_code', $installment) && $installment['bar_code'] != NULL) {
                if (!PaymentRequestHasInstallments::where('bar_code', $installment['bar_code'])
                    ->where('payment_request_id', $id)
                    ->exists()) {
                    if (!self::checkInvoiceOrBilletExists('bar_code', $installment['bar_code'], $requestInfo)) {
                        return response()->json([
                            'erro' => 'O código do boleto ou sistema na conta' .
                                PaymentRequest::with('installments')
                                ->whereRelation('installments', 'bar_code', '=', $installment['bar_code'])
                                ->first()->id .
                                ', para cadastrar essa conta deve ser apagada.'
                        ], 409);
                        break;
                    }
                }
            }
            if (array_key_exists('billet_number', $installment) && $installment['billet_number'] != NULL) {
                if (!PaymentRequestHasInstallments::where('billet_number', $installment['billet_number'])
                    ->where('payment_request_id', $id)
                    ->exists()) {
                    if (!self::checkInvoiceOrBilletProviderExists('billet_number', $installment['billet_number'], $requestInfo)) {
                        return response()->json([
                            'erro' => 'O número do boleto já foi cadastrado para este fornecedor na conta ' .
                                PaymentRequest::with('provider')
                                ->where('billet_number', $installment['billet_number'])
                                ->whereRelation('provider', 'id', '=', $requestInfo['provider_id'])->first()->id .
                                '.'
                        ], 409);
                        break;
                    }
                    if (!self::checkInvoiceOrBilletExists('billet_number', $installment['billet_number'], $requestInfo)) {
                        if (!$requestInfo['force_registration']) {
                            return response()->json([
                                'erro' => 'O número do boleto já foi cadastrado no sistema em outro fornecedor na conta ' .
                                    PaymentRequest::with('installments')
                                    ->whereRelation('installments', 'billet_number', '=', $installment['billet_number'])
                                    ->first()->id .
                                    ', tem certeza que deseja cadastrar mesmo assim?'
                            ], 424);
                            break;
                        }
                    }
                }
            }
        }

        if (!self::checkInstallmentsPurchaseOrder($request->all())) {
            return response()->json([
                'erro' => 'É necessário que informa ao menos parcela para cada pedido de compra.'
            ], 422);
        }

        return $this->paymentRequestService->putPaymentRequest($id, $request);
    }

    public function destroy($id)
    {
        $paymentRequestService = $this->paymentRequestService->deletePaymentRequest($id);
        return response('');
    }

    public function import()
    {
        $this->paymentRequestImport->import(request()->file('import_file'));
        return response('');
    }

    public function groupFormPayment(Request $request)
    {
        return $this->paymentRequestService->getAllGroupFormPayment($request->all());
    }

    public function updateDateInstallment(PutInstallmentsRequest $request)
    {
        return $this->paymentRequestService->updateDateInstallment($request->all());
    }

    public function checkInstallmentsPurchaseOrder($requestInfo)
    {
        if (array_key_exists('purchase_orders', $requestInfo)) {
            $idInstallments = [];
            if (array_key_exists('installment_purchase_order', $requestInfo)) {
                foreach ($requestInfo['installment_purchase_order'] as $installment) {
                    array_push($idInstallments, $installment['installment']);
                }
            }

            foreach ($requestInfo['purchase_orders'] as $purchaseOrder) {
                $informedInstallment  = PurchaseOrderHasInstallments::where('purchase_order_id', $purchaseOrder['order'])
                    ->whereIn('id', $idInstallments)
                    ->exists();

                if (!$informedInstallment) {
                    return false;
                    break;
                }
            }
        }
        return true;
    }

    public function checkInvoiceOrBilletProviderExists($attribute, $value, $requestInfo)
    {
        if ($attribute == 'bar_code' or $attribute == 'billet_number') {
            if (PaymentRequest::with(['provider', 'installments'])
                ->whereRelation('installments', $attribute, '=', $value)
                ->whereRelation('provider', 'id', '=', $requestInfo['provider_id'])
                ->exists()
            ) {
                return false;
            }
        } else if (PaymentRequest::with('provider')
            ->where($attribute, $value)
            ->whereRelation('provider', 'id', '=', $requestInfo['provider_id'])
            ->exists()
        ) {
            return false;
        }
        return true;
    }
    public function checkInvoiceOrBilletExists($attribute, $value, $requestInfo)
    {
        if ($attribute == 'bar_code' or $attribute == 'billet_number') {
            if (PaymentRequest::with(['provider', 'installments'])
                ->whereRelation('installments', $attribute, '=', $value)
                ->exists()
            ) {
                if ($requestInfo['force_registration']) {
                    return true;
                }
                return false;
            }
        } else if (PaymentRequest::where($attribute, $value)
            ->exists()
        ) {
            return false;
        }
        return true;
    }

    public function updateInstallment(Request $request, $id)
    {
        return $this->paymentRequestService->updateInstallment($id, $request);
    }
}
