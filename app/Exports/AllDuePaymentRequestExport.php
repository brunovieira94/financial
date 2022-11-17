<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequest;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Config;

class AllDuePaymentRequestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $result = PaymentRequest::with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
        $requestInfo = $this->requestInfo;
        $result = Utils::baseFilterReportsPaymentRequest($result, $requestInfo);
        $result = $result->whereHas('installments', function ($query) use ($requestInfo) {
            if (array_key_exists('from', $requestInfo)) {
                $query = $query->where('extension_date', '>=', $requestInfo['from']);
            }
            if (array_key_exists('to', $requestInfo)) {
                $query = $query->where('extension_date', '<=', $requestInfo['to']);
            }
            if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                $query = $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
        });
        return $result->get();
        //return PaymentRequest::with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'])->get();
    }

    public function map($paymentRequest): array
    {
        $this->totalTax = 0;
        foreach ($paymentRequest->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $paymentRequest->id,
            $paymentRequest->company->company_name ?? '',
            $paymentRequest->company->cnpj ?? '',
            $paymentRequest->business ? $paymentRequest->business->name : $paymentRequest->business,
            $paymentRequest->chart_of_accounts ? $paymentRequest->chart_of_accounts->title : $paymentRequest->chart_of_accounts,
            $paymentRequest->cost_center ? $paymentRequest->cost_center->title : $paymentRequest->cost_center,
            ExportsUtils::costCenterVPName($paymentRequest),
            ExportsUtils::costCenterManagers($paymentRequest),
            $paymentRequest->provider ? ($paymentRequest->provider->cnpj ? 'CNPJ: ' . $paymentRequest->provider->cnpj : 'CPF: ' . $paymentRequest->provider->cpf) : $paymentRequest->provider,
            $paymentRequest->provider ? ($paymentRequest->provider->company_name ? $paymentRequest->provider->company_name : $paymentRequest->provider->full_name) : $paymentRequest->provider,
            ExportsUtils::providerAlias($paymentRequest),
            ExportsUtils::formatDate($paymentRequest->created_at),
            ExportsUtils::formatDate($paymentRequest->emission_date),
            ExportsUtils::formatDate($paymentRequest->pay_date),
            ExportsUtils::formatDate($paymentRequest->next_extension_date),
            $paymentRequest->days_late,
            ExportsUtils::formatDate(ExportsUtils::logFirstApprovalFinancialAnalyst($paymentRequest)['created_at']),
            ExportsUtils::logFirstApprovalFinancialAnalyst($paymentRequest)['user_name'],
            ExportsUtils::formatDate(ExportsUtils::cnabGeneratedPaymentDate($paymentRequest)),
            $paymentRequest->currency ? $paymentRequest->currency->title : $paymentRequest->currency,
            $paymentRequest->exchange_rate,
            $this->totalTax,
            $paymentRequest->amount,
            $paymentRequest->net_value,
            ExportsUtils::amountToPay($paymentRequest),
            ExportsUtils::accountType($paymentRequest),
            $paymentRequest->invoice_number,
            $paymentRequest->invoice_type,
            ExportsUtils::frequencyOfInstallments($paymentRequest),
            ExportsUtils::numberOfInstallments($paymentRequest),
            $paymentRequest->user ? $paymentRequest->user->email : $paymentRequest->user,
            Config::get('constants.statusPt.'.$paymentRequest->approval->status),
            $paymentRequest->approval->approver_stage_first['title'],
            ExportsUtils::approver($paymentRequest),
            $paymentRequest->note,
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
