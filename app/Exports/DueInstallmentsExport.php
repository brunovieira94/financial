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

class DueInstallmentsExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $filterCanceled = false;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $query = PaymentRequestHasInstallments::query();
        $query = $query->with(['cnab_generated_installment', 'payment_request', 'group_payment', 'bank_account_provider']);
        $requestInfo = $this->requestInfo;

        $query->whereHas('payment_request', function ($query) use ($requestInfo) {
            if (array_key_exists('from', $requestInfo)) {
                $query = $query->where('extension_date', '>=', $requestInfo['from']);
            }
            if (array_key_exists('to', $requestInfo)) {
                $query = $query->where('extension_date', '<=', $requestInfo['to']);
            }
            if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                $query = $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $query = Utils::baseFilterReportsInstallment($query, $requestInfo);

        return $query->get();
    }

    public function map($query): array
    {
        return [
            $query->payment_request->id,
            $query->parcel_number,
            $query->payment_request->company->company_name ?? '',
            $query->payment_request->company->cnpj ?? '',
            $query->payment_request->business->name ?? '',
            $query->payment_request->chart_of_accounts->title ?? '',
            $query->payment_request->cost_center->title ?? '',
            ExportsUtils::costCenterVPName($query->payment_request),
            ExportsUtils::costCenterManagers($query->payment_request),
            $query->payment_request->provider ? ($query->payment_request->provider->cnpj ? 'CNPJ: ' . $query->payment_request->provider->cnpj : 'CPF: ' . $query->payment_request->provider->cpf) : $query->payment_request->provider,
            $query->payment_request->provider->trade_name ?? '',
            ExportsUtils::providerAlias($query->payment_request),
            ExportsUtils::formatDate($query->payment_request->created_at),
            ExportsUtils::formatDate($query->payment_request->emission_date),
            ExportsUtils::formatDate($query->due_date),
            ExportsUtils::formatDate($query->extension_date),
            ExportsUtils::installmentsDaysLate($query),
            ExportsUtils::formatDate(ExportsUtils::logFirstApprovalFinancialAnalyst($query->payment_request)['created_at']),
            ExportsUtils::logFirstApprovalFinancialAnalyst($query->payment_request)['user_name'],
            ExportsUtils::formatDate(ExportsUtils::installmentsCnabGeneratedPaymentDate($query)),
            $query->initial_value ?? '',
            $query->fees,
            $query->fine,
            $query->discount,
            ExportsUtils::installmentTotalFinalValue($query),
            $query->group_payment->title ?? '',
            $query->billet_number ?? '',
            ExportsUtils::translatedInstallmentBilletType($query),
            $query->bar_code ?? '',
            $query->bank_account_provider->entity_type ?? '',
            $query->bank_account_provider->entity_name ?? '',
            $query->bank_account_provider->cpf_cnpj ?? '',
            $query->bank_account_provider->bank->title ?? '',
            $query->bank_account_provider->agency_number ?? '',
            $query->bank_account_provider->agency_check_number ?? '',
            ExportsUtils::translatedInstallmentBankAccountType($query),
            $query->bank_account_provider->account_number ?? '',
            $query->bank_account_provider->account_check_number ?? '',
            $query->payment_request->user->name ?? '',
            $query->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $query->payment_request->approval->status),
            ExportsUtils::approver($query->payment_request),
            $query->note,
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
