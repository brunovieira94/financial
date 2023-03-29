<?php

namespace App\Services;

use App\Http\Requests\OtherPaymentsRequest;

use App\Models\OtherPayment;
use App\Models\OtherPaymentHasAttachments;
use App\Models\OtherPaymentHasExchangeRates;
use App\Models\PaymentRequestHasInstallmentsThatHaveOtherPayments;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\PaymentRequestClean;

use App\Exports\Utils as ExportUtils;

use Illuminate\Foundation\Http\FormRequest;
use Config;
use DB;

class OtherPaymentsService
{
    private $otherPayment;
    private $otherPaymentHasAttachments;
    private $otherPaymentHasExchangeRates;
    private $paymentRequestHasInstallments;
    private $paymentRequestHasInstallmentsThatHaveOtherPayments;

    public function __construct(
        OtherPayment $otherPayment,
        OtherPaymentHasAttachments $otherPaymentHasAttachments,
        OtherPaymentHasExchangeRates $otherPaymentHasExchangeRates,
        PaymentRequestHasInstallments $paymentRequestHasInstallments,
        PaymentRequestHasInstallmentsThatHaveOtherPayments $paymentRequestHasInstallmentsThatHaveOtherPayments,
    ) {
        $this->otherPayment = $otherPayment;
        $this->otherPaymentHasAttachments = $otherPaymentHasAttachments;
        $this->otherPaymentHasExchangeRates = $otherPaymentHasExchangeRates;
        $this->paymentRequestHasInstallments = $paymentRequestHasInstallments;
        $this->paymentRequestHasInstallmentsThatHaveOtherPayments = $paymentRequestHasInstallmentsThatHaveOtherPayments;
    }

    public function storeImported($userId, $installmentInfo, $importFile)
    {
        $otherPayment = $this->otherPayment->create([
            'group_form_payment_id' => $installmentInfo['group_form_payment_id'],
            'bank_account_company_id' => $installmentInfo['bank_account_company_id'],
            'system_payment_method' => $installmentInfo['system_payment_method'],
            'payment_date' => $installmentInfo['payment_date'],
            'user_id' => $userId,
            'import_file' => $importFile,
        ]);

        $this->updateSingleInstallmentStateToPaid(
            $installmentInfo['installment_id'],
            $installmentInfo,
            $installmentInfo['system_payment_method']
        );

        $installment = $this->paymentRequestHasInstallments->findOrFail($installmentInfo['installment_id']);
        if(!PaymentRequestClean::with('installments')->where('id', $installment->payment_request_id)->whereHas('installments', function ($query) {
            $query->where('status', '!=', Config::get('constants.status.paid out'));
        })->exists()) {
            DB::table('accounts_payable_approval_flows')->where('payment_request_id', $installment->payment_request_id)->update(['status' => Config::get('constants.status.paid out')]);
        }
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
            'system_payment_method' => Config::get('constants.systemPaymentMethod.gui'),
            'user_id' => $request->user()->id,
        ]);

        $this->storeAttachments($request, $requestInfo, $otherPayment->id);
        $this->storeExchangeRate($requestInfo, $otherPayment->id);
        $this->storeInstallmentsOtherPayments($requestInfo, $otherPayment->id);
        $this->updateInstallmentsStateToPaidOut($requestInfo, Config::get('constants.systemPaymentMethod.gui'));
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

    public function resolvePaymentRequestsStates($requestInfo)
    {
        if (!array_key_exists('payment_request_ids', $requestInfo))
            return;

        $paymentRequests = PaymentRequestClean::with(['installments', 'approval'])->whereIn('id', $requestInfo['payment_request_ids'])->get();

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

    private function updateInstallmentsStateToPaidOut($requestInfo, $systemPaymentMethod)
    {
        if (!array_key_exists('installments_ids', $requestInfo) || is_null($requestInfo['installments_ids']))
            return;

        foreach ($requestInfo['installments_ids'] as $installmentId) {
            $this->updateSingleInstallmentStateToPaid($installmentId, $requestInfo, $systemPaymentMethod);
        }
    }

    private function updateSingleInstallmentStateToPaid($installmentId, $requestInfo, $systemPaymentMethod)
    {
        $installment = $this->paymentRequestHasInstallments->findOrFail($installmentId);
        $installment->status = Config::get('constants.status.paid out');
        $installment->bank_account_company_id = $requestInfo['bank_account_company_id'];
        $installment->group_form_payment_made_id = $requestInfo['group_form_payment_id'];
        $installment->paid_value = $requestInfo['paid_value'] ?? ExportUtils::installmentTotalFinalValue($installment);
        $installment->payment_made_date = $requestInfo['payment_date'];
        $installment->system_payment_method = $systemPaymentMethod;
        $installment->save();
        Utils::paiOutInstallmentLinked($installmentId);
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

    private function storeAttachments(FormRequest $request, $requestInfo, $otherPaymentId)
    {
        if (!array_key_exists('attachments', $requestInfo) || is_null($requestInfo['attachments']))
            return;

        foreach ($requestInfo['attachments'] as $key => $attachment) {
            $attachmentRecord = $this->otherPaymentHasAttachments->create([
                'attachment' => $this->saveFile($request, "attachments.{$key}.attachment", "attachment"),
                'other_payment_id' => $otherPaymentId,
            ]);
        }
    }

    public function saveFile(FormRequest $request, $fileName, $storagePath)
    {
        $dateTimeIdentifier = uniqid(date('HisYmd'));

        if ($request->hasFile($fileName) && $request->file($fileName)->isValid()) {
            $nameSplit = explode('.', $request[$fileName]->getClientOriginalName());
            $extension  = $nameSplit[count($nameSplit) - 1];
            $newName = "{$nameSplit[0]}_{$dateTimeIdentifier}.{$extension}";
            $fileStored = $request[$fileName]->storeAs($storagePath, $newName);

            if ($fileStored) {
                return $newName;
            }
        }

        return response('Falha ao realizar o upload do arquivo.', 500)->send();
    }
}
