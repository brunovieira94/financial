<?php

namespace App\Http\Controllers;

use App\Http\Requests\PutInstallmentsRequest;
use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequestRequest;
use App\Services\PaymentRequestService as PaymentRequestService;
use App\Http\Requests\PutPaymentRequestRequest;
use App\Imports\PaymentRequestsImport;
use App\Models\PaymentRequest;
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
        $attribute = null;

        if (array_key_exists('bar_code', $request->all())) {
            $attribute = 'bar_code';
            $value = $request->bar_code;
        } else if (array_key_exists('invoice_number', $request->all())) {
            $attribute = 'invoice_number';
            $value = $request->invoice_number;
        }

        if ($attribute != null) {
            if (PaymentRequest::with('provider')
                ->where($attribute, $value)
                ->whereRelation('provider', 'id', '=', $request->provider_id)
                ->exists()
            ) {
                return response()->json([
                    'erro' => 'Este número de nota fiscal, boleto ou invoice já foi cadastrado para este fornecedor na conta ' .
                        PaymentRequest::with('provider')
                        ->where($attribute, $value)
                        ->whereRelation('provider', 'id', '=', $request->provider_id)->first()->id .
                        '.'
                ], 409);
            }
            if (PaymentRequest::where($attribute, $value)
                ->exists()
            ) {
                if ($request->force_registration) {
                    return $this->paymentRequestService->postPaymentRequest($request);
                }
                return response()->json([
                    'erro' => 'O número da nota fiscal, boleto ou invoice já foi cadastrado no sistema em outro fornecedor na conta ' .
                        PaymentRequest::where($attribute, $value)
                        ->first()->id .
                        ', tem certeza que deseja cadastrar mesmo assim?'
                ], 424);
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
        $attribute = null;
        $provider_id = null;

        if (array_key_exists('bar_code', $request->all())) {
            $attribute = 'bar_code';
            $value = $request->bar_code;
        } else if (array_key_exists('invoice_number', $request->all())) {
            $attribute = 'invoice_number';
            $value = $request->invoice_number;
        }

        if ($attribute != null) {
            $paymentRequest = PaymentRequest::with('provider')->findOrFail($id);
            $columnValidation = '';

            if ($paymentRequest->bar_code == null) {
                $columnValidation = $paymentRequest->invoice_number;
            } else {
                $columnValidation = $paymentRequest->bar_code;
            }
            if ($columnValidation == $value) {
                return $this->paymentRequestService->putPaymentRequest($id, $request);
            }

            if (array_key_exists('provider_id', $request->all())) {
                $provider_id = $request->provider_id;
            } else {
                $provider_id = $paymentRequest->provider_id;
            }

            if (PaymentRequest::with('business')
                ->where($attribute, $value)
                ->whereRelation('provider', 'id', '=', $provider_id)
                ->exists()
            ) {
                return response()->json([
                    'erro' => 'Este número de nota fiscal, boleto ou invoice já foi cadastrado para este fornecedor na conta ' .
                        PaymentRequest::with('business')
                        ->where($attribute, $value)
                        ->whereRelation('provider', 'id', '=', $provider_id)->first()->id .
                        '.'
                ], 409);
            }
            if (PaymentRequest::where($attribute, $value)
                ->exists()
            ) {
                if ($request->force_registration) {
                    return $this->paymentRequestService->putPaymentRequest($id, $request);
                }
                return response()->json([
                    'erro' => 'Já existe a nota fiscal, boleto ou invoice cadastrado no sistema na conta ' .
                        PaymentRequest::where($attribute, $value)
                        ->first()
                        ->id .
                        '.'
                ], 424);
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
<<<<<<< HEAD
=======

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
            return true;
        }
    }
>>>>>>> develop
}
