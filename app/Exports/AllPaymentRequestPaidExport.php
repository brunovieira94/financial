<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllPaymentRequestPaidExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        return AccountsPayableApprovalFlow::with(['payment_request'])
        ->where('status', 4)
        ->whereRelation('payment_request', 'deleted_at', '=', null)
        ->get();
    }

    public function map($accountsPayableApprovalFlow): array
    {
        $this->totalTax = 0;
        foreach ($accountsPayableApprovalFlow->payment_request->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->cnpj ? $accountsPayableApprovalFlow->payment_request->provider->cnpj : $accountsPayableApprovalFlow->payment_request->provider->cpf) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->company_name ? $accountsPayableApprovalFlow->payment_request->provider->company_name : $accountsPayableApprovalFlow->payment_request->provider->full_name) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->emission_date,
            $accountsPayableApprovalFlow->payment_request->pay_date,
            $accountsPayableApprovalFlow->payment_request->amount,
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
            $accountsPayableApprovalFlow->payment_request->net_value,
            $accountsPayableApprovalFlow->payment_request->created_at,
            $this->totalTax,
        ];
    }

    public function headings(): array
    {
        return [
            'CNPJ do Fornecedor',
            'Nome do Fornecedor',
            'Data de Emissão',
            'Data de Pagamento',
            'Valor',
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
            'Valor Líquido',
            'Data de Criação',
            'Total de Impostos',
        ];
    }
}
