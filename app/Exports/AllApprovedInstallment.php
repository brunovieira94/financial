<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequestHasInstallments;
use App\Services\Utils;
use Config;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllApprovedInstallment implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $installment = PaymentRequestHasInstallments::with(['payment_request', 'group_payment', 'bank_account_provider']);

        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', 1);
            });
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);
        if (!array_key_exists('company', $requestInfo))
            return collect([]);

        return $installment->get();
    }

    public function map($installment): array
    {
        return [
            $installment->payment_request->id,
            $installment->parcel_number,
            $installment->payment_request->company->company_name ?? '',
            $installment->payment_request->company->cnpj ?? '',
            $installment->payment_request->business->name ?? '',
            $installment->payment_request->chart_of_accounts->title ?? '',
            $installment->payment_request->cost_center->title ?? '',
            ExportsUtils::costCenterVPName($installment->payment_request),
            ExportsUtils::costCenterManagers($installment->payment_request),
            $installment->payment_request->provider ? ($installment->payment_request->provider->cnpj ? 'CNPJ: ' . $installment->payment_request->provider->cnpj : 'CPF: ' . $installment->payment_request->provider->cpf) : $installment->payment_request->provider,
            $installment->payment_request->provider->trade_name ?? '',
            ExportsUtils::providerAlias($installment->payment_request),
            ExportsUtils::formatDate($installment->payment_request->created_at),
            ExportsUtils::formatDate($installment->payment_request->emission_date),
            ExportsUtils::formatDate($installment->due_date),
            ExportsUtils::formatDate($installment->extension_date),
            ExportsUtils::installmentsDaysLate($installment),
            ExportsUtils::formatDate(ExportsUtils::logFirstApprovalFinancialAnalyst($installment->payment_request)['created_at']),
            ExportsUtils::logFirstApprovalFinancialAnalyst($installment->payment_request)['user_name'],
            ExportsUtils::formatDate(ExportsUtils::installmentsCnabGeneratedPaymentDate($installment)),
            $installment->initial_value ?? '',
            $installment->fees,
            $installment->fine,
            $installment->discount,
            ExportsUtils::installmentTotalFinalValue($installment),
            $installment->group_payment->title ?? '',
            $installment->billet_number ?? '',
            ExportsUtils::translatedInstallmentBilletType($installment),
            $installment->bar_code ?? '',
            $installment->bank_account_provider->entity_type ?? '',
            $installment->bank_account_provider->entity_name ?? '',
            $installment->bank_account_provider->cpf_cnpj ?? '',
            $installment->bank_account_provider->bank->title ?? '',
            $installment->bank_account_provider->agency_number ?? '',
            $installment->bank_account_provider->agency_check_number ?? '',
            ExportsUtils::translatedInstallmentBankAccountType($installment),
            $installment->bank_account_provider->account_number ?? '',
            $installment->bank_account_provider->account_check_number ?? '',
            $installment->payment_request->user->name ?? '',
            $installment->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $installment->payment_request->approval->status),
            ExportsUtils::approver($installment->payment_request),
            $installment->note,
        ];
    }

    public function headings(): array
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
            'Banco',
            'Agência',
            'Dígito da Agência',
            'Tipo de Conta Bancária',
            'Conta Bancária',
            'Dígito da Conta Bancária',
            'Usuário',
            'Etapa Atual',
            'Status Atual',
            'Aprovador',
            'Observações',
        ];
    }
}
