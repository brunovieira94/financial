<?php

namespace App\Exports;

use App\Models\CnabPaymentRequestsHasInstallments;
use App\Models\OtherPayment;
use App\Models\PaymentRequestHasInstallmentsThatHaveOtherPayments;
use Carbon\Carbon;

use Config;
use CreatePaymentRequestInstallmentsHaveOtherPayments;

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
                return $carry + Utils::installmentTotalFinalValue($item);
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

    public static function  translatedInstallmentBankAccountType($accountType)
    {
        if (is_null($accountType))
            return '';
        switch ($accountType) {
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

    public static function exportPaymentRequestData($paymentRequest)
    {
        $totalTax = 0;
        if (isset($paymentRequest->tax)) {
            foreach ($paymentRequest->tax as $value) {
                $totalTax += $value['tax_amount'];
            }
        }

        return [
            $paymentRequest->id,
            $paymentRequest->company->company_name ?? '',
            $paymentRequest->company->cnpj ?? '',
            $paymentRequest->business ? $paymentRequest->business->name : $paymentRequest->business,
            $paymentRequest->chart_of_accounts ? $paymentRequest->chart_of_accounts->title : $paymentRequest->chart_of_accounts,
            $paymentRequest->cost_center ? $paymentRequest->cost_center->title : $paymentRequest->cost_center,
            self::costCenterVPName($paymentRequest),
            self::costCenterManagers($paymentRequest),
            $paymentRequest->provider ? ($paymentRequest->provider->cnpj ? 'CNPJ: ' . $paymentRequest->provider->cnpj : 'CPF: ' . $paymentRequest->provider->cpf) : $paymentRequest->provider,
            $paymentRequest->provider ? ($paymentRequest->provider->company_name ? $paymentRequest->provider->company_name : $paymentRequest->provider->full_name) : $paymentRequest->provider,
            self::providerAlias($paymentRequest),
            self::formatDate($paymentRequest->created_at),
            self::formatDate($paymentRequest->emission_date),
            self::formatDate($paymentRequest->pay_date),
            self::formatDate($paymentRequest->next_extension_date),
            $paymentRequest->days_late,
            self::formatDate(self::logFirstApprovalFinancialAnalyst($paymentRequest)['created_at']),
            self::logFirstApprovalFinancialAnalyst($paymentRequest)['user_name'],
            self::formatDate(self::cnabGeneratedPaymentDate($paymentRequest)),
            $paymentRequest->currency ? $paymentRequest->currency->title : $paymentRequest->currency,
            $paymentRequest->currency_old ? $paymentRequest->currency_old->title : $paymentRequest->currency_old,
            $paymentRequest->exchange_rate,
            $totalTax,
            $paymentRequest->amount,
            $paymentRequest->net_value,
            self::amountToPay($paymentRequest),
            self::accountType($paymentRequest),
            $paymentRequest->invoice_number,
            $paymentRequest->invoice_type,
            self::frequencyOfInstallments($paymentRequest),
            self::numberOfInstallments($paymentRequest),
            $paymentRequest->user ? $paymentRequest->user->email : $paymentRequest->user,
            Config::get('constants.statusPt.' . $paymentRequest->approval->status),
            $paymentRequest->approval->approver_stage_first['title'],
            self::approver($paymentRequest),
            $paymentRequest->note,
        ];
    }

    public static function exportPaymentRequestColumn()
    {
        return [
            'Id',
            'Empresa',
            'CNPJ da Empresa',
            'Negócio',
            'Plano de Contas',
            'Centro de Custo',
            'VPs do Centro de Custo',
            'Gestores do Centro de Custo',
            'Identificação do Fornecedor',
            'Razão Social',
            'Apelido do Fornecedor',
            'Data de Criação',
            'Data de Emissão',
            'Data de Vencimento',
            'Data de Pagamento',
            'Dias de atraso',
            'Data Aprovação CAP',
            'Analista CAP',
            'Pagamento Realizado',
            'Moeda',
            'Moeda Inicial',
            'Taxa de Câmbio',
            'Total de Impostos',
            'Valor Bruto',
            'Valor Líquido',
            'Valor a Pagar',
            'Tipo de Conta',
            'Número do Documento',
            'Tipo de fatura',
            'Frequência de Parcelas',
            'Número de Parcelas',
            'Usuário',
            'Status Atual',
            'Etapa Atual',
            'Aprovador',
            'Observações',
        ];
    }

    public static function exportInstallmentData($installment)
    {
        $bankAccountCompany = self::bankAccountCompanyInstallment($installment);

        return [
            $installment->payment_request->id,
            $installment->parcel_number,
            $installment->payment_request->company->company_name ?? '',
            $installment->payment_request->company->cnpj ?? '',
            $installment->payment_request->business->name ?? '',
            $installment->payment_request->chart_of_accounts->title ?? '',
            $installment->payment_request->cost_center->title ?? '',
            self::costCenterVPName($installment->payment_request),
            self::costCenterManagers($installment->payment_request),
            $installment->payment_request->provider ? ($installment->payment_request->provider->cnpj ? 'CNPJ: ' . $installment->payment_request->provider->cnpj : 'CPF: ' . $installment->payment_request->provider->cpf) : $installment->payment_request->provider,
            $installment->payment_request->provider->trade_name ?? '',
            self::providerAlias($installment->payment_request),
            self::formatDate($installment->payment_request->created_at),
            self::formatDate($installment->payment_request->emission_date),
            self::formatDate($installment->due_date),
            self::formatDate($installment->extension_date),
            self::installmentsDaysLate($installment),
            self::formatDate(self::logFirstApprovalFinancialAnalyst($installment->payment_request)['created_at']),
            self::logFirstApprovalFinancialAnalyst($installment->payment_request)['user_name'],
            self::formatDate(self::installmentsCnabGeneratedPaymentDate($installment)),
            $installment->initial_value ?? '',
            $installment->fees,
            $installment->fine,
            $installment->discount,
            self::installmentTotalFinalValue($installment),
            $installment->group_payment->title ?? '',
            $installment->billet_number ?? '',
            self::translatedInstallmentBilletType($installment),
            $installment->bar_code ?? '',
            $installment->bank_account_provider->entity_type ?? '',
            $installment->bank_account_provider->entity_name ?? '',
            $installment->bank_account_provider->cpf_cnpj ?? '',
            $installment->bank_account_provider->bank->title ?? '',
            $installment->bank_account_provider->agency_number ?? '',
            $installment->bank_account_provider->agency_check_number ?? '',
            self::translatedInstallmentBankAccountType(($installment->bank_account_provider->account_type ?? null)),
            $installment->bank_account_provider->account_number ?? '',
            $installment->bank_account_provider->account_check_number ?? '',
            $installment->payment_request->user->name ?? '',
            $installment->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $installment->payment_request->approval->status),
            self::approver($installment->payment_request),
            $installment->note,
            $bankAccountCompany == null ? '' : $bankAccountCompany->bank->title ?? '',
            $bankAccountCompany == null ? '' : $bankAccountCompany->agency_number ?? '',
            $bankAccountCompany == null ? '' : $bankAccountCompany->agency_check_number ?? '',
            self::translatedInstallmentBankAccountType(($bankAccountCompany->account_type ?? null)),
            $bankAccountCompany == null ? '' : $bankAccountCompany->account_number ?? '',
            $bankAccountCompany == null ? '' : $bankAccountCompany->account_check_number ?? '',
        ];
    }

    public static function exportInstallmentColumn()
    {
        return [
            'Conta',
            'Parcela',
            'Empresa',
            'CNPJ Empresa',
            'Negócio',
            'Plano de Contas',
            'Centro de Custo',
            'VPs do Centro de Custo',
            'Gestores do Centro de Custo',
            'Identificação do Fornecedor',
            'Razao Social',
            'Apelido do Fornecedor',
            'Data de Criação',
            'Data de Emissão',
            'Data de Vencimento',
            'Data de Pagamento',
            'Dias de Atraso',
            'Data de Aprovação CAP',
            'Analista CAP',
            'Pagamento Realizado',
            'Valor Inicial',
            'Juros',
            'Multa',
            'Desconto',
            'Valor a Pagar',
            'Forma de Pagamento',
            'Número do Boleto',
            'Tipo de Boleto',
            'Código do Boleto',
            'Tipo de Pessoa',
            'Nome/Razão Social',
            'CPF/CNPJ',
            'Banco - Fornecedor',
            'Agência - Fornecedor',
            'Dígito da Agência - Fornecedor',
            'Tipo de Conta Bancária - Fornecedor',
            'Conta Bancária - Fornecedor',
            'Dígito da Conta Bancária - Fornecedor',
            'Usuário',
            'Etapa Atual',
            'Status Atual',
            'Aprovador',
            'Observações',
            'Banco - Empresa',
            'Agência - Empresa',
            'Dígito da Agência - Empresa',
            'Tipo de Conta Bancária - Empresa',
            'Conta Bancária - Empresa',
            'Dígito da Conta Bancária - Empresa',
        ];
    }

    public static function bankAccountCompanyInstallment($installment)
    {
        $bankAccount = null;

        if (PaymentRequestHasInstallmentsThatHaveOtherPayments::where('payment_request_installment_id', $installment->id)->exists()) {
            $paymentRequestHasInstallmentsThatHaveOtherPayments = PaymentRequestHasInstallmentsThatHaveOtherPayments::with('other_payment.bank_account_company.bank')->where('payment_request_installment_id', $installment->id)->orderBy('id', 'DESC')->first();
            $bankAccount = $paymentRequestHasInstallmentsThatHaveOtherPayments->other_payment->bank_account_company;
        }

        if (CnabPaymentRequestsHasInstallments::where('installment_id', $installment->id)->exists()) {
            $cnabPaymentRequestsHasInstallment = CnabPaymentRequestsHasInstallments::with('generated_cnab.bank_account_company.bank')->where('installment_id', $installment->id)->orderBy('id', 'DESC')->first();
            $bankAccount = $cnabPaymentRequestsHasInstallment->generated_cnab->bank_account_company;
        }

        return $bankAccount;
    }
}
