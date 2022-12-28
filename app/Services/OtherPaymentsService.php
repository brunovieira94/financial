<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\OtherPaymentsRequest;

use App\Models\OtherPayment;
use App\Models\OtherPaymentHasAttachments;
use App\Models\OtherPaymentHasExchangeRates;
use App\Models\PaymentRequestHasInstallmentsThatHaveOtherPayments;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use Config;

class OtherPaymentsService
{
    private $otherPayment;
    private $otherPaymentHasAttachments;
    private $otherPaymentHasExchangeRates;
    private $paymentRequestHasInstallments;
    private $paymentRequestHasInstallmentsThatHaveOtherPayments;

    public function __construct(OtherPayment $otherPayment, OtherPaymentHasAttachments $otherPaymentHasAttachments, OtherPaymentHasExchangeRates $otherPaymentHasExchangeRates, PaymentRequestHasInstallments $paymentRequestHasInstallments, PaymentRequestHasInstallmentsThatHaveOtherPayments $paymentRequestHasInstallmentsThatHaveOtherPayments)
    {
        $this->otherPayment = $otherPayment;
        $this->otherPaymentHasAttachments = $otherPaymentHasAttachments;
        $this->otherPaymentHasExchangeRates = $otherPaymentHasExchangeRates;
        $this->paymentRequestHasInstallments = $paymentRequestHasInstallments;
        $this->paymentRequestHasInstallmentsThatHaveOtherPayments = $paymentRequestHasInstallmentsThatHaveOtherPayments;
    }

    public function storePayment(OtherPaymentsRequest $request)
    {
        $requestInfo = $request->all();

        if (array_key_exists('all', $requestInfo) && $requestInfo['all']) {
            $installments = PaymentRequestHasInstallmentsClean::with('payment_request');
            $installments = $installments->whereHas('payment_request', function ($query) {
                $query->whereHas('approval', function ($query) {
                    $query->where('status', 1);
                });
            });
            $installments = $installments
                ->where('status', '!=', Config::get('constants.status.paid out'))
                ->where('status', '!=', Config::get('constants.status.cnab generated'))
                ->get();
            $requestInfo['installments_ids'] = $installments->pluck('id')->toArray();
            $requestInfo['payment_request_ids'] = array_unique($installments->pluck('payment_request_id')->toArray());
        }

        if (!array_key_exists('payment_request_ids', $requestInfo) && array_key_exists('installments_ids', $requestInfo)) {
            $installments = PaymentRequestHasInstallmentsClean::whereIn('id', $requestInfo['installments_ids']);
            $requestInfo['payment_request_ids'] = array_unique($installments->pluck('payment_request_id')->toArray());
        }

        $otherPayment = $this->otherPayment->create([
            'group_form_payment_id' => $requestInfo['group_form_payment_id'],
            'bank_account_company_id' => $requestInfo['bank_account_company_id'],
            'note' => array_key_exists('note', $requestInfo) ? $requestInfo['note'] : null,
            'payment_date' => $requestInfo['payment_date'],
            'user_id' => $request->user()->id,
        ]);

        $this->storeAttachments($request, $requestInfo, $otherPayment->id);

        if ($requestInfo['group_form_payment_id'] == 5)
            $this->storeExchangeRate($requestInfo, $otherPayment->id);

        $this->storeInstallmentsOtherPayments($requestInfo, $otherPayment->id);
        $this->updateInstallmentsStateToPaidOut($requestInfo);
        $this->resolvePaymentRequestsStates($requestInfo);

        return response()->json(['success' => 'Sucesso'], 200);
    }

    public function checkOverPaymentRequestsStatus()
    {
        $approvedPaymentRequests = PaymentRequestClean::with('approval')
            ->whereHas('approval', function ($query) {
                $query->where('status', 1);
            })->get();

        $this->resolvePaymentRequestsStates([
            'payment_request_ids' => $approvedPaymentRequests->pluck('id')->toArray(),
        ]);
    }

    private function resolvePaymentRequestsStates($requestInfo)
    {
        if (!array_key_exists('payment_request_ids', $requestInfo))
            return;

        $paymentRequests = PaymentRequest::with(['installments', 'approval'])->whereIn('id', $requestInfo['payment_request_ids'])->get();

        foreach ($paymentRequests as $paymentRequest) {
            $allPaid = true;
            $allPaidOrCnab = true;

            foreach ($paymentRequest->installments as $installment) {
                if ($installment->status != Config::get('constants.status.paid out')) {
                    $allPaid = false;
                }
                if ($installment->status != Config::get('constants.status.cnab generated') && $installment->status != Config::get('constants.status.paid out')) {
                    $allPaidOrCnab = false;
                }

                if (!$allPaid && !$allPaidOrCnab) {
                    break;
                }
            }

            if ($allPaid) {
                $paymentRequest->approval->status = Config::get('constants.status.paid out');
                $paymentRequest->approval->save();
            } else if ($allPaidOrCnab) {
                $paymentRequest->approval->status = Config::get('constants.status.cnab generated');
                $paymentRequest->approval->save();
            }
        }
    }

    private function updateInstallmentsStateToPaidOut($requestInfo)
    {
        if (!array_key_exists('installments_ids', $requestInfo) || is_null($requestInfo['installments_ids']))
            return;

        foreach ($requestInfo['installments_ids'] as $installmentId) {
            $installment = $this->paymentRequestHasInstallments->findOrFail($installmentId);
            $installment->status = Config::get('constants.status.paid out');
            $installment->save();
        }
    }

    private function storeInstallmentsOtherPayments($requestInfo, $otherPaymentId)
    {
        if (!array_key_exists('installments_ids', $requestInfo) || is_null($requestInfo['installments_ids']))
            return;

        foreach ($requestInfo['installments_ids'] as $installmentId) {
            $installmentOtherPayment = $this->paymentRequestHasInstallmentsThatHaveOtherPayments->create([
                'payment_request_installment_id' => $installmentId,
                'other_payment_id' => $otherPaymentId,
            ]);
        }
    }

    private function storeExchangeRate($requestInfo, $otherPaymentId)
    {
        if (!array_key_exists('exchange_rates', $requestInfo) || is_null($requestInfo['exchange_rates']))
            return;

        foreach ($requestInfo['exchange_rates'] as $exchangeRate) {
            $exchangeRateRecord = $this->otherPaymentHasExchangeRates->create([
                'currency_id' => $exchangeRate['currency_id'],
                'exchange_rate' => $exchangeRate['exchange_rate'],
                'other_payment_id' => $otherPaymentId,
            ]);
        }
    }

    private function storeAttachments(OtherPaymentsRequest $request, $requestInfo, $otherPaymentId)
    {
        if (!array_key_exists('attachments', $requestInfo) || is_null($requestInfo['attachments']))
            return;

        foreach ($requestInfo['attachments'] as $key => $attachment) {
            $attachmentRecord = $this->otherPaymentHasAttachments->create([
                'attachment' => $this->storeAttachmentFile($request, $key),
                'other_payment_id' => $otherPaymentId,
            ]);
        }
    }

    private function storeAttachmentFile(OtherPaymentsRequest $request, $key)
    {
        $dateTimeIdentifier = uniqid(date('HisYmd'));
        $keyIdentifier = "attachments.{$key}.attachment";

        if ($request->hasFile($keyIdentifier) && $request->file($keyIdentifier)->isValid()) {
            $attachmentFileExtension = $request[$keyIdentifier]->extension();
            $attachmentFileOriginalName = explode('.', $request[$keyIdentifier]->getClientOriginalName());
            $fileName = "{$attachmentFileOriginalName[0]}_{$dateTimeIdentifier}.{$attachmentFileExtension}";
            $uploadFileResult = $request[$keyIdentifier]->storeAs('attachment', $fileName);

            if (!$uploadFileResult)
                return response('Falha ao realizar o upload do arquivo.', 500)->send();
            return $fileName;
        }
    }
}
