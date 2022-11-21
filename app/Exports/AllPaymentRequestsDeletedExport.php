<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\AccountsPayableApprovalFlow;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Config;

class AllPaymentRequestsDeletedExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
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
        $accountsPayableApprovalFlow = AccountsPayableApprovalFlow::with(['payment_request_trashed']);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request_trashed', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        return $accountsPayableApprovalFlow
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
            $accountsPayableApprovalFlow->payment_request_trashed->id,
            $accountsPayableApprovalFlow->payment_request_trashed->company->company_name ?? '',
            $accountsPayableApprovalFlow->payment_request_trashed->company->cnpj ?? '',
            $accountsPayableApprovalFlow->payment_request_trashed->business ? $accountsPayableApprovalFlow->payment_request_trashed->business->name : $accountsPayableApprovalFlow->payment_request_trashed->business,
            $accountsPayableApprovalFlow->payment_request_trashed->chart_of_accounts ? $accountsPayableApprovalFlow->payment_request_trashed->chart_of_accounts->title : $accountsPayableApprovalFlow->payment_request_trashed->chart_of_accounts,
            $accountsPayableApprovalFlow->payment_request_trashed->cost_center ? $accountsPayableApprovalFlow->payment_request_trashed->cost_center->title : $accountsPayableApprovalFlow->payment_request_trashed->cost_center,
            ExportsUtils::costCenterVPName($accountsPayableApprovalFlow->payment_request_trashed),
            ExportsUtils::costCenterManagers($accountsPayableApprovalFlow->payment_request_trashed),
            $accountsPayableApprovalFlow->payment_request_trashed->provider ? ($accountsPayableApprovalFlow->payment_request_trashed->provider->cnpj ? 'CNPJ: ' . $accountsPayableApprovalFlow->payment_request_trashed->provider->cnpj : 'CPF: ' . $accountsPayableApprovalFlow->payment_request_trashed->provider->cpf) : $accountsPayableApprovalFlow->payment_request_trashed->provider,
            $accountsPayableApprovalFlow->payment_request_trashed->provider ? ($accountsPayableApprovalFlow->payment_request_trashed->provider->company_name ? $accountsPayableApprovalFlow->payment_request_trashed->provider->company_name : $accountsPayableApprovalFlow->payment_request_trashed->provider->full_name) : $accountsPayableApprovalFlow->payment_request_trashed->provider,
            ExportsUtils::providerAlias($accountsPayableApprovalFlow->payment_request_trashed),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request_trashed->created_at),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request_trashed->emission_date),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request_trashed->pay_date),
            ExportsUtils::formatDate($accountsPayableApprovalFlow->payment_request_trashed->next_extension_date),
            $accountsPayableApprovalFlow->payment_request_trashed->days_late,
            ExportsUtils::formatDate(ExportsUtils::logFirstApprovalFinancialAnalyst($accountsPayableApprovalFlow->payment_request_trashed)['created_at']),
            ExportsUtils::logFirstApprovalFinancialAnalyst($accountsPayableApprovalFlow->payment_request_trashed)['user_name'],
            ExportsUtils::formatDate(ExportsUtils::cnabGeneratedPaymentDate($accountsPayableApprovalFlow->payment_request_trashed)),
            $accountsPayableApprovalFlow->payment_request_trashed->currency ? $accountsPayableApprovalFlow->payment_request_trashed->currency->title : $accountsPayableApprovalFlow->payment_request_trashed->currency,
            $accountsPayableApprovalFlow->payment_request_trashed->exchange_rate,
            $this->totalTax,
            $accountsPayableApprovalFlow->payment_request_trashed->amount,
            $accountsPayableApprovalFlow->payment_request_trashed->net_value,
            ExportsUtils::amountToPay($accountsPayableApprovalFlow->payment_request_trashed),
            ExportsUtils::accountType($accountsPayableApprovalFlow->payment_request_trashed),
            $accountsPayableApprovalFlow->payment_request_trashed->invoice_number,
            $accountsPayableApprovalFlow->payment_request_trashed->invoice_type,
            ExportsUtils::frequencyOfInstallments($accountsPayableApprovalFlow->payment_request_trashed),
            ExportsUtils::numberOfInstallments($accountsPayableApprovalFlow->payment_request_trashed),
            $accountsPayableApprovalFlow->payment_request_trashed->user ? $accountsPayableApprovalFlow->payment_request_trashed->user->email : $accountsPayableApprovalFlow->payment_request_trashed->user,
            Config::get('constants.statusPt.'.$accountsPayableApprovalFlow->payment_request_trashed->approval->status),
            $accountsPayableApprovalFlow->payment_request_trashed->approval->approver_stage_first['title'],
            ExportsUtils::approver($accountsPayableApprovalFlow->payment_request_trashed),
            $accountsPayableApprovalFlow->payment_request_trashed->note,
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
