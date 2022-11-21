<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\FormPayment;
use App\Services\Utils;
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

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $accountsPayableApprovalFlow = AccountsPayableApprovalFlow::with(['payment_request']);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->where('status', 1);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });

        if (!array_key_exists('company', $requestInfo))
            return collect([]);

        return $accountsPayableApprovalFlow->get();
    }

    public function map($accountsPayableApprovalFlow): array
    {
        $this->totalTax = 0;

        foreach ($accountsPayableApprovalFlow->payment_request->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $accountsPayableApprovalFlow->payment_request->id,
            $accountsPayableApprovalFlow->payment_request->company->company_name ?? '',
            $accountsPayableApprovalFlow->payment_request->company->cnpj ?? '',
            $accountsPayableApprovalFlow->payment_request->business ? $accountsPayableApprovalFlow->payment_request->business->name : $accountsPayableApprovalFlow->payment_request->business,
            $accountsPayableApprovalFlow->payment_request->chart_of_accounts ? $accountsPayableApprovalFlow->payment_request->chart_of_accounts->title : $accountsPayableApprovalFlow->payment_request->chart_of_accounts,
            $accountsPayableApprovalFlow->payment_request->cost_center ? $accountsPayableApprovalFlow->payment_request->cost_center->title : $accountsPayableApprovalFlow->payment_request->cost_center,
            ExportsUtils::costCenterVPName($accountsPayableApprovalFlow->payment_request),
            ExportsUtils::costCenterManagers($accountsPayableApprovalFlow->payment_request),
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->cnpj ? 'CNPJ: ' . $accountsPayableApprovalFlow->payment_request->provider->cnpj : 'CPF: ' . $accountsPayableApprovalFlow->payment_request->provider->cpf) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->company_name ? $accountsPayableApprovalFlow->payment_request->provider->company_name : $accountsPayableApprovalFlow->payment_request->provider->full_name) : $accountsPayableApprovalFlow->payment_request->provider,
            ExportsUtils::providerAlias($accountsPayableApprovalFlow->payment_request),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request->created_at),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request->emission_date),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request->pay_date),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request->next_extension_date),
            $accountsPayableApprovalFlow->payment_request->days_late,
            ExportsUtils::formatDate(ExportsUtils::logFirstApprovalFinancialAnalyst($accountsPayableApprovalFlow->payment_request)['created_at']),
            ExportsUtils::logFirstApprovalFinancialAnalyst($accountsPayableApprovalFlow->payment_request)['user_name'],
            ExportsUtils::formatDate(ExportsUtils::cnabGeneratedPaymentDate($accountsPayableApprovalFlow->payment_request)),
            $accountsPayableApprovalFlow->payment_request->currency ? $accountsPayableApprovalFlow->payment_request->currency->title : $accountsPayableApprovalFlow->payment_request->currency,
            $accountsPayableApprovalFlow->payment_request->exchange_rate,
            $this->totalTax,
            $accountsPayableApprovalFlow->payment_request->amount,
            $accountsPayableApprovalFlow->payment_request->net_value,
            ExportsUtils::amountToPay($accountsPayableApprovalFlow->payment_request),
            ExportsUtils::accountType($accountsPayableApprovalFlow->payment_request),
            $accountsPayableApprovalFlow->payment_request->invoice_number,
            $accountsPayableApprovalFlow->payment_request->invoice_type,
            ExportsUtils::frequencyOfInstallments($accountsPayableApprovalFlow->payment_request),
            ExportsUtils::numberOfInstallments($accountsPayableApprovalFlow->payment_request),
            $accountsPayableApprovalFlow->payment_request->user ? $accountsPayableApprovalFlow->payment_request->user->email : $accountsPayableApprovalFlow->payment_request->user,
            Config::get('constants.statusPt.'.$accountsPayableApprovalFlow->payment_request->approval->status),
            $accountsPayableApprovalFlow->payment_request->approval->approver_stage_first['title'],
            ExportsUtils::approver($accountsPayableApprovalFlow->payment_request),
            $accountsPayableApprovalFlow->payment_request->note,
        ];
    }

    public function headings(): array
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
}
