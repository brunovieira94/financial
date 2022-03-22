<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllPaymentRequestsDeletedExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;


    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        return AccountsPayableApprovalFlow::with(['payment_request_trashed'])
        ->whereRelation('payment_request_trashed', 'deleted_at', '!=', null)
        ->get();
    }

    public function map($accountsPayableApprovalFlow): array
    {
        $this->totalTax = 0;
        foreach ($accountsPayableApprovalFlow->payment_request_trashed->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $accountsPayableApprovalFlow->payment_request_trashed->id + 1000,
            $accountsPayableApprovalFlow->payment_request_trashed->provider ? ($accountsPayableApprovalFlow->payment_request_trashed->provider->cnpj ? 'CNPJ: '.$accountsPayableApprovalFlow->payment_request_trashed->provider->cnpj : 'CPF: '. $accountsPayableApprovalFlow->payment_request_trashed->provider->cpf) : $accountsPayableApprovalFlow->payment_request_trashed->provider,
            $accountsPayableApprovalFlow->payment_request_trashed->provider ? ($accountsPayableApprovalFlow->payment_request_trashed->provider->company_name ? $accountsPayableApprovalFlow->payment_request_trashed->provider->company_name : $accountsPayableApprovalFlow->payment_request_trashed->provider->full_name) : $accountsPayableApprovalFlow->payment_request_trashed->provider,
            $accountsPayableApprovalFlow->payment_request_trashed->emission_date,
            $accountsPayableApprovalFlow->payment_request_trashed->pay_date,
            $accountsPayableApprovalFlow->payment_request_trashed->amount,
            $accountsPayableApprovalFlow->payment_request_trashed->net_value,
            $this->totalTax,
            $accountsPayableApprovalFlow->payment_request_trashed->chart_of_accounts ? $accountsPayableApprovalFlow->payment_request_trashed->chart_of_accounts->title : $accountsPayableApprovalFlow->payment_request_trashed->chart_of_accounts,
            $accountsPayableApprovalFlow->payment_request_trashed->cost_center ? $accountsPayableApprovalFlow->payment_request_trashed->cost_center->title : $accountsPayableApprovalFlow->payment_request_trashed->cost_center,
            $accountsPayableApprovalFlow->payment_request_trashed->business ? $accountsPayableApprovalFlow->payment_request_trashed->business->name : $accountsPayableApprovalFlow->payment_request_trashed->business,
            $accountsPayableApprovalFlow->payment_request_trashed->currency ? $accountsPayableApprovalFlow->payment_request_trashed->currency->title : $accountsPayableApprovalFlow->payment_request_trashed->currency,
            $accountsPayableApprovalFlow->payment_request_trashed->exchange_rate,
            $accountsPayableApprovalFlow->payment_request_trashed->frequency_of_installments,
            $accountsPayableApprovalFlow->payment_request_trashed->days_late,
            $accountsPayableApprovalFlow->payment_request_trashed->payment_type,
            $accountsPayableApprovalFlow->payment_request_trashed->user ? $accountsPayableApprovalFlow->payment_request_trashed->user->email : $accountsPayableApprovalFlow->payment_request_trashed->user,
            $accountsPayableApprovalFlow->payment_request_trashed->invoice_number,
            $accountsPayableApprovalFlow->payment_request_trashed->invoice_type,
            $accountsPayableApprovalFlow->payment_request_trashed->bar_code,
            $accountsPayableApprovalFlow->payment_request_trashed->next_extension_date,
            $accountsPayableApprovalFlow->payment_request_trashed->created_at,
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
        ];
    }
}
