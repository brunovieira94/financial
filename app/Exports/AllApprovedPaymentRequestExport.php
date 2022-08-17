<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\FormPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Config;

class AllApprovedPaymentRequestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        if((!array_key_exists('form_payment_id', $this->requestInfo) || $this->requestInfo['form_payment_id'] == 0) && array_key_exists('company_id', $this->requestInfo)){
            return AccountsPayableApprovalFlow::with(['payment_request'])
            ->where('status', 1)
            ->whereRelation('payment_request', 'deleted_at', '=', null)
            ->whereRelation('payment_request', 'company_id', '=', $this->requestInfo['company_id'])
            ->get();

        }elseif(!array_key_exists('form_payment_id', $this->requestInfo) || $this->requestInfo['form_payment_id'] == 0){
            return AccountsPayableApprovalFlow::with(['payment_request'])
            ->where('status', 1)
            ->whereRelation('payment_request', 'deleted_at', '=', null)
            ->get();
        }

        $formPayment = FormPayment::findOrFail($this->requestInfo['form_payment_id']);

        if($formPayment->group_form_payment_id == 1) {
            if($formPayment->same_ownership)
            {
                if(!array_key_exists('company_id', $this->requestInfo))
                {
                    return AccountsPayableApprovalFlow::with('payment_request')
                    ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                    ->whereRelation('payment_request', 'deleted_at', '=', null)
                    ->whereRelation('payment_request', 'bar_code', 'like', "{$formPayment->bank_code}%")
                    ->where('status', 1)
                    ->get();
                }else {
                    return AccountsPayableApprovalFlow::with('payment_request')
                    ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                    ->whereRelation('payment_request', 'deleted_at', '=', null)
                    ->whereRelation('payment_request', 'bar_code', 'like', "{$formPayment->bank_code}%")
                    ->whereRelation('payment_request', 'company_id', '=', $this->requestInfo['company_id'])
                    ->where('status', 1)
                    ->get();
                }
            } else {
                if(!array_key_exists('company_id', $this->requestInfo))
                {
                    return AccountsPayableApprovalFlow::with('payment_request')
                    ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                    ->whereRelation('payment_request', 'deleted_at', '=', null)
                    ->whereRelation('payment_request', 'bar_code', 'not like', "{$formPayment->bank_code}%")
                    ->where('status', 1)
                    ->get();
                } else {
                    return AccountsPayableApprovalFlow::with('payment_request')
                    ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                    ->whereRelation('payment_request', 'deleted_at', '=', null)
                    ->whereRelation('payment_request', 'bar_code', 'not like', "{$formPayment->bank_code}%")
                    ->whereRelation('payment_request', 'company_id', '=', $this->requestInfo['company_id'])
                    ->where('status', 1)
                    ->get();
                }
            }
        } else {
            if(!array_key_exists('company_id', $this->requestInfo))
            {
                return AccountsPayableApprovalFlow::with('payment_request')
                ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id) // arrumar
                ->whereRelation('payment_request', 'deleted_at', '=', null)
                ->where('status', 1)
                ->get();
            } else {
                return AccountsPayableApprovalFlow::with('payment_request')
                ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id) // arrumar
                ->whereRelation('payment_request', 'deleted_at', '=', null)
                ->whereRelation('payment_request', 'company_id', '=', $this->requestInfo['company_id'])
                ->where('status', 1)
                ->get();
            }

        }
    }

    public function map($accountsPayableApprovalFlow): array
    {
        $this->totalTax = 0;

        foreach ($accountsPayableApprovalFlow->payment_request->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $accountsPayableApprovalFlow->payment_request->id,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->cnpj ? 'CNPJ: '. $accountsPayableApprovalFlow->payment_request->provider->cnpj : 'CPF: '. $accountsPayableApprovalFlow->payment_request->provider->cpf) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->company_name ? $accountsPayableApprovalFlow->payment_request->provider->company_name : $accountsPayableApprovalFlow->payment_request->provider->full_name) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->emission_date,
            $accountsPayableApprovalFlow->payment_request->pay_date,
            $accountsPayableApprovalFlow->payment_request->amount,
            $accountsPayableApprovalFlow->payment_request->net_value,
            $this->totalTax,
            $accountsPayableApprovalFlow->payment_request->chart_of_accounts ? $accountsPayableApprovalFlow->payment_request->chart_of_accounts->title : $accountsPayableApprovalFlow->payment_request->chart_of_accounts,
            $accountsPayableApprovalFlow->payment_request->cost_center ? $accountsPayableApprovalFlow->payment_request->cost_center->title : $accountsPayableApprovalFlow->payment_request->cost_center,
            $accountsPayableApprovalFlow->payment_request->business ? $accountsPayableApprovalFlow->payment_request->business->name : $accountsPayableApprovalFlow->payment_request->business,
            $accountsPayableApprovalFlow->payment_request->currency ? $accountsPayableApprovalFlow->payment_request->currency->title : $accountsPayableApprovalFlow->payment_request->currency,
            $accountsPayableApprovalFlow->payment_request->exchange_rate,
            $accountsPayableApprovalFlow->payment_request->frequency_of_installments,
            $accountsPayableApprovalFlow->payment_request->days_late,
            $accountsPayableApprovalFlow->payment_request->payment_type,
            $accountsPayableApprovalFlow->payment_request->user ? $accountsPayableApprovalFlow->payment_request->user->email : $accountsPayableApprovalFlow->payment_request->user,
            $accountsPayableApprovalFlow->payment_request->invoice_number,
            $accountsPayableApprovalFlow->payment_request->invoice_type,
            $accountsPayableApprovalFlow->payment_request->bar_code,
            $accountsPayableApprovalFlow->payment_request->next_extension_date,
            $accountsPayableApprovalFlow->payment_request->created_at,
            $accountsPayableApprovalFlow->payment_request->note,
            $accountsPayableApprovalFlow->payment_request->approval->approval_stage_first['title'],
            Config::get('constants.statusPt.'.$accountsPayableApprovalFlow->payment_request->approval->status)
        ];
    }

    public function headings(): array
    {
        return [
            'Id',
            'Identificação do Fornecedor',
            'Nome do Fornecedor',
            'Data de Emissão',
            'Data de Pagamento',
            'Valor',
            'Valor Líquido',
            'Total de Impostos',
            'Plano de Contas',
            'Centro de Custo',
            'Negócio',
            'Moeda',
            'Taxa de Câmbio',
            'Frequência de Parcelas',
            'Dias de atraso',
            'Tipo de pagamento',
            'Usuário',
            'Número da fatura',
            'Tipo de fatura',
            'Código de barras',
            'Pŕoxima data de prorrogação',
            'Data de Criação',
            'Observações',
            'Etapa Atual',
            'Status Atual'
        ];
    }
}
