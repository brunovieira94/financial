<?php

namespace App\Exports;

use App\Models\CnabPaymentRequestsHasInstallments;
use App\Models\Export;
use App\Models\OtherPayment;
use App\Models\PaymentRequestHasInstallmentsThatHaveOtherPayments;

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
                return $carry + self::installmentTotalFinalValue($item);
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
                return 'Adiantamento';
            case 2:
                return 'Avulso/Reembolsos';
            case 3:
                return 'Invoice';
            case 4:
                return 'Imposto';
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
            case 2:
                return 'Guia de Impostos';
            case 4:
                return 'Boleto de Concessionária';
            case 5:
                return 'Boleto Jurídico';
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
            $paymentRequest->or,
            $paymentRequest->hash,
            $paymentRequest->admin_id,
            $paymentRequest->process_number,
            $paymentRequest->allow_binding == true ? 'Sim' : 'Não',
            $paymentRequest['installment_link'],
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
            'OR',
            'HASH',
            'Admin ID',
            'Numero do Processo'
            'Agrupar Parcela',
            'Parcelas Agrupadas'
        ];
    }

    public static function exportBillingData($billing)
    {
        /** @var Cangooroo*/
        $cangooroo = $billing->cangooroo;
        /** @var Hotel*/
        $hotel = !is_null($cangooroo) ? $cangooroo->hotel : null;
        /** @var BankAccount*/
        $bankAccount = $billing->bank_account;
        /** @var HotelReasonToReject*/
        $reasonToReject = $billing->reason_to_reject;
        return [
            !is_null($billing->user) ? $billing->user->name : '',
            $billing->reserve,
            $billing->cangooroo_service_id,
            $billing->payment_status,
            !is_null($cangooroo) ? $cangooroo->status : '',
            $billing->status_123,
            Config::get('constants.statusPt.' . $billing->approval_status),
            !is_null($billing->approval_flow) && !is_null($billing->approval_flow->role) ? $billing->approval_flow->role->title : '',
            $billing->billing_payment_id,
            $billing->supplier_value,
            $billing->boleto_value,
            $billing->pay_date,
            $billing->boleto_code,
            $billing->remark,
            $billing->oracle_protocol,
            !is_null($cangooroo) ? $cangooroo['123_id'] : '',
            !is_null($cangooroo) ? $cangooroo->supplier_name : '',
            !is_null($cangooroo) ? $cangooroo->reservation_date : '',
            !is_null($cangooroo) ? $cangooroo->check_in : '',
            !is_null($cangooroo) ? $cangooroo->check_out : '',
            !is_null($cangooroo) ? $cangooroo->hotel_id : '',
            !is_null($cangooroo) ? $cangooroo->supplier_hotel_id : '',
            !is_null($cangooroo) ? $cangooroo->hotel_name : '',
            (!is_null($hotel) && !is_null($hotel->billing_type)) ? $hotel->billingTypes[$hotel->billing_type] : '',
            !is_null($billing->form_of_payment) ? $billing->formsOfPayment[$billing->form_of_payment] : '',
            !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->title : '') : '',
            !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->bank_code : '') : '',
            !is_null($bankAccount) ? (!!($bankAccount->agency_check_number) || $bankAccount->agency_check_number === '0' ? $bankAccount->agency_number.'-'.$bankAccount->agency_check_number : $bankAccount->agency_number) : '',
            !is_null($bankAccount) && !is_null($bankAccount->account_type) ? $bankAccount->accountTypes[$bankAccount->account_type] : '',
            !is_null($bankAccount) ? (!!($bankAccount->account_check_number) || $bankAccount->account_check_number === '0' ? $bankAccount->account_number.'-'.$bankAccount->account_check_number : $bankAccount->account_number) : '',
            $billing->recipient_name,
            $billing->cnpj,
            !is_null($hotel) ? ($hotel->is_valid ? 'Sim' : 'Não') : '',
            !is_null($cangooroo) ? $cangooroo->selling_price : '',
            $billing->pax_in_house ? 'Sim' : 'Não',
            $billing->created_at,
            $billing->updated_at,
            !is_null($reasonToReject) ? $reasonToReject->title : '',
            $billing->suggestion,
            $billing->suggestion_reason,
            '',
            '',
        ];
    }

    public static function exportBillingColumn()
    {
        return [
            'Operador',
            'Reserva',
            'Id do Serviço',
            'Status do Pagamento',
            'Status do Cangooroo',
            'Status 123',
            'Status de Aprovação',
            'Etapa de Aprovação',
            'Id do Pagamento',
            'Valor do Parceiro',
            'Valor do Boleto',
            'Data de pagamento',
            'Código do Boleto',
            'Observação',
            'Protocolo Oracle',
            'ID 123',
            'Parceiro',
            'Data da Reserva',
            'Data Check-in',
            'Data do Check-out',
            'ID Hotel - Cangooroo',
            'ID Hotel - Parceiro',
            'Nome do Hotel',
            'Tipo de Faturamento',
            'Forma de pagamento',
            'Banco',
            'Código do Banco',
            'Agência',
            'Tipo de Conta',
            'Conta',
            'Nome Completo do Titular',
            'CNPJ',
            'CNPJ Válido?',
            'Valor Cangooroo',
            'Pax In House',
            'Data de Criação',
            'Data de Alteração',
            'Motivo de Rejeição',
            'Sugestão',
            'Motivo',
            'Pago',
            'Obs Pagamento'
        ];
    }

    public static function exportInstallmentData($installment)
    {
        $bankAccountCompany = $installment->bank_account_company == null ? self::bankAccountCompanyInstallment($installment) : $installment->bank_account_company;

        $verification_period = null;
        if (isset($installment->verification_period)) {
            $verification_period = implode(', ', $installment->verification_period->toArray());
        }

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
            $installment->reference_number ?? '',
            $installment->revenue_code ?? '',
            $installment->tax_file_phone_number ?? '',
            $verification_period ?? '',
            $installment->card_identifier,
            $installment->group_payment_received->title ?? '',
            $installment->paid_value ?? '',
            self::formatDate($installment->payment_made_date) ?? '',
            $installment->client_identifier ?? '',
            $installment->client_name ?? '',
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
            'Número de Referência',
            'Código de Receita',
            'Número de telefone do arquivo fiscal',
            'Período de verificação',
            'Identificador do Cartão',
            'Forma de pagamento - Pago',
            'Valor - Pago',
            'Data do pagamento - Pago',
            'Identificação do Cliente',
            'Nome do Cliente',
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

    public static function exportFile($requestInfo, $nameReport)
    {
        $extension = '.xlsx';
        if (array_key_exists('exportFormat', $requestInfo)) {
            if ($requestInfo['exportFormat'] == 'csv') {
                $extension = '.csv';
            }
        }

        $nameFile = $nameReport . '_' . uniqid(date('HisYmd')) . $extension;
        $path = 'exports' . '/' . $nameFile;

        $export = Export::create([
            'status' => false,
            'user_id' => auth()->user()->id,
            'path' => $path,
            'name' => $nameReport . date(' - d/m/Y H:i'),
            'extension' => str_replace('.', '', $extension),
        ]);

        return [
            'path' => $path,
            'export' => $export,
            'extension' => $extension,
            'nameFile' => $nameFile
        ];
    }
}
