<?php

namespace App\Exports;

use Carbon\Carbon;

use Config;

class Utils
{
    public static function logFirstApprovalFinancialAnalyst($paymentRequest)
    {
        $namesDate = [];
        if ($paymentRequest->first_approval_financial_analyst != null) {
            $namesDate['user_name'] = $paymentRequest->first_approval_financial_analyst['user_name'];
            $namesDate['created_at'] = $paymentRequest->first_approval_financial_analyst['created_at'];
        } else {
            $namesDate['user_name'] = null;
            $namesDate['created_at'] = null;
        }
        return $namesDate;
    }

    public static function costCenterVPName($paymentRequest)
    {
        $vps = '';

        if (isset($paymentRequest->cost_center)) {
            foreach ($paymentRequest->cost_center->getVicePresidentsAttribute() as $vp) {
                $vps = $vps == '' ? $vp->name : $vps . ', ' . $vp->name;
            }
        }

        return $vps;
    }

    public static function costCenterManagers($paymentRequest)
    {
        $managers = '';

        if (isset($paymentRequest->cost_center)) {
            foreach ($paymentRequest->cost_center->getManagersAttribute() as $manager) {
                $managers = $managers == '' ? $manager->name : $managers . ', ' . $manager->name;
            }
        }

        return $managers;
    }

    public static function providerAlias($paymentRequest)
    {
        return $paymentRequest->provider ? $paymentRequest->provider->alias ?? '' : '';
    }

    public static function amountToPay($paymentRequest)
    {
        $amountToPay = 0;

        if (isset($paymentRequest->installments)) {
            $amountToPay = $paymentRequest->installments->reduce(function ($carry, $item) {
                return $carry + $item['portion_amount'];
            }, 0);
        }

        return $amountToPay;
    }

    public static function cnabGeneratedPaymentDate($paymentRequest)
    {
        $date = null;

        if (isset($paymentRequest->cnab_payment_request) && isset($paymentRequest->cnab_payment_request->cnab_generated)) {
            $date = $paymentRequest->cnab_payment_request->cnab_generated->file_date;
        }

        return $date ?? '';
    }

    public static function installmentsCnabGeneratedPaymentDate($paymentRequestInstallment)
    {
        $date = null;

        if (isset($paymentRequestInstallment->cnab_generated_installment) && isset($paymentRequestInstallment->cnab_generated_installment->generated_cnab)) {
            $date = $paymentRequestInstallment->cnab_generated_installment->generated_cnab->file_date;
        }

        return $date ?? '';
    }

    public static function installmentsDaysLate($paymentRequestInstallment)
    {
        $daysLate = null;

        if (isset($paymentRequestInstallment->extension_date) && $paymentRequestInstallment->status != Config::get('constants.status.paid out')) {
            $daysLate = date_diff(date_create($paymentRequestInstallment->extension_date), now());
        }

        return isset($daysLate) ? $daysLate->days : '';
    }

    public static function accountType($paymentRequest)
    {
        if (is_null($paymentRequest->payment_type))
            return '';
        switch ($paymentRequest->payment_type) {
            case 0:
                return 'Nota Fiscal';
            case 1:
                return 'Boleto';
            case 2:
                return 'Avulso';
            case 3:
                return 'Invoice';
            default:
                return 'Outro';
        }
    }

    public static function frequencyOfInstallments($paymentRequest)
    {
        if (is_null($paymentRequest->frequency_of_installments))
            return '';
        switch ($paymentRequest->frequency_of_installments) {
            case 1:
                return 'Diário';
            case 7:
                return 'Semanal';
            case 10:
                return 'Decêndio';
            case 15:
                return 'Quinzenal';
            case 30:
                return 'Mensal';
            case 365:
                return 'Anual';
            default:
                if (is_int($paymentRequest->frequency_of_installments) && $paymentRequest->frequency_of_installments > 0)
                    return 'Cada ' . $paymentRequest->frequency_of_installments . ' dias';
                return 'Outro';
        }
    }

    public static function numberOfInstallments($paymentRequest)
    {
        return isset($paymentRequest->installments) ? $paymentRequest->installments->count() : 0;
    }

    public static function approver($paymentRequest)
    {
        if ($paymentRequest->approval->status != Config::get('constants.status.open') || $paymentRequest->approval->status == Config::get('constants.status.disapproved'))
            return '';

        $approver = '';

        if (isset($paymentRequest->approval) && isset($paymentRequest->approval->approver_stage)) {
            foreach ($paymentRequest->approval->approver_stage as $approver_stage) {
                $approver = $approver == ''
                    ? $approver_stage['name']
                    : ($approver_stage['name'] != '' ? $approver . ', ' . $approver_stage['name'] : $approver);
            }
        }

        return $approver;
    }

    public static function translatedInstallmentBilletType($paymentRequestInstallment)
    {
        if (is_null($paymentRequestInstallment->type_billet) || is_null($paymentRequestInstallment->billet_number))
            return '';
        switch ($paymentRequestInstallment->type_billet) {
            case 0:
                return 'Boleto Bancário';
            case 1:
                return 'Boleto de Arrecadação';
            default:
                return 'Outro';
        }
    }

    public static function translatedInstallmentBankAccountType($paymentRequestInstallment)
    {
        if (is_null($paymentRequestInstallment->bank_account_provider) || is_null($paymentRequestInstallment->bank_account_provider->account_type))
            return '';
        switch ($paymentRequestInstallment->bank_account_provider->account_type) {
            case 0:
                return 'Poupança';
            case 1:
                return 'Conta Corrente';
            case 2:
                return 'Conta Salário';
            default:
                return 'Outro';
        }
    }

    public static function installmentTotalFinalValue($paymentRequestInstallment)
    {
        $total = $paymentRequestInstallment->initial_value ?? $paymentRequestInstallment->portion_amount ?? 0;
        $total += ($paymentRequestInstallment->fees ?? 0) + ($paymentRequestInstallment->fine ?? 0);
        $total -= ($paymentRequestInstallment->discount ?? 0);
        return $total < 0 ? 0 : $total;
    }

    /**
     * Reformats the date format from `yyyy-mm-dd` to `dd/mm/yyyy`.
     * If the value received is a timestamp with the following configuration `yyyy-mm-dd hh:mm:ss`
     * it will be reformatted to `dd/mm/yyyy hh:mm:ss`.
     * */
    public static function formatDate($date)
    {
        if (is_null($date)) {
            return '';
        } else if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/i', $date) == 1) {
            return Carbon::createFromFormat('Y-m-d', $date)->format('d/m/Y');
        } else if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/i', $date) == 1) {
            $splittedDate = explode(' ', $date);
            return Utils::formatDate($splittedDate[0]) . ' ' . $splittedDate[1];
        } else {
            return $date;
        }
    }
}
