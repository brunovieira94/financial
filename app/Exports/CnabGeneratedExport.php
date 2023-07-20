<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use App\Models\CnabPaymentRequestsHasInstallments;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallments;
use App\Models\UserHasPaymentRequest;
use App\Services\Utils;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromCollection;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;
use Carbon\Carbon;
use Config;
use Illuminate\Contracts\Queue\ShouldQueue;

class CnabGeneratedExport implements FromCollection, WithMapping, WithHeadings
{
    private $requestInfo;
    private $id;
    private $fileName;

    public function __construct($requestInfo, $id, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->id = $id;
        $this->fileName = $fileName;
    }

    use Exportable;

    public function collection()
    {
        return CnabPaymentRequestsHasInstallments::with(
            [
                'generated_cnab.user',
                'generated_cnab.user',
                'generated_cnab.company',
                'generated_cnab.bank_account_company.bank',
                'installment.payment_request',
                'installment.bank_account_provider',
            ]
        )->where('cnab_generated_id', $this->id)->get();
    }

    public function map($installmentsCnabGenerated): array
    {
        return [
            $installmentsCnabGenerated->installment->payment_request_id,
            $installmentsCnabGenerated->installment->parcel_number,
            $installmentsCnabGenerated->generated_cnab->company->trade_name ?? '',
            $installmentsCnabGenerated->generated_cnab->company->cnpj ?? '',
            $installmentsCnabGenerated->generated_cnab->bank_account_company->bank->title ?? '',
            $installmentsCnabGenerated->generated_cnab->bank_account_company->agency_number ?? '',
            $installmentsCnabGenerated->generated_cnab->bank_account_company->agency_check_number ?? '',
            $installmentsCnabGenerated->generated_cnab->bank_account_company->account_number ?? '',
            $installmentsCnabGenerated->generated_cnab->bank_account_company->account_check_number ?? '',
            $installmentsCnabGenerated->generated_cnab->bank_account_company->covenant ?? '',
            $installmentsCnabGenerated->installment->payment_request->business->name ?? '',
            $installmentsCnabGenerated->installment->payment_request->chart_of_accounts->title ?? '',
            $installmentsCnabGenerated->installment->payment_request->cost_center->title ?? '',
            ExportsUtils::costCenterVPName($installmentsCnabGenerated->installment->payment_request),
            ExportsUtils::costCenterManagers($installmentsCnabGenerated->installment->payment_request),
            $installmentsCnabGenerated->installment->payment_request->provider ? ($installmentsCnabGenerated->installment->payment_request->provider->cnpj ? 'CNPJ: ' . $installmentsCnabGenerated->installment->payment_request->provider->cnpj : 'CPF: ' . $installmentsCnabGenerated->installment->payment_request->provider->cpf) : $installmentsCnabGenerated->installment->payment_request->provider,
            $installmentsCnabGenerated->installment->payment_request->provider->trade_name ?? '',
            ExportsUtils::providerAlias($installmentsCnabGenerated->installment->payment_request),
            ExportsUtils::formatDate($installmentsCnabGenerated->installment->payment_request->created_at),
            ExportsUtils::formatDate($installmentsCnabGenerated->installment->payment_request->emission_date),
            ExportsUtils::formatDate($installmentsCnabGenerated->installment->extension_date == null ? $installmentsCnabGenerated->installment->due_date : $installmentsCnabGenerated->installment->extension_date),
            ExportsUtils::formatDate($installmentsCnabGenerated->installment->payment_request->next_extension_date),
            ExportsUtils::installmentsDaysLate($installmentsCnabGenerated->installment),
            ExportsUtils::formatDate(ExportsUtils::logFirstApprovalFinancialAnalyst($installmentsCnabGenerated->installment->payment_request)['created_at']),
            ExportsUtils::logFirstApprovalFinancialAnalyst($installmentsCnabGenerated->installment->payment_request)['user_name'],
            $installmentsCnabGenerated->generated_cnab->user->name ?? '',
            ExportsUtils::formatDate($installmentsCnabGenerated->generated_cnab->file_date),
            $installmentsCnabGenerated->installment->text_cnab ?? '',
            ExportsUtils::formatDate(ExportsUtils::installmentsCnabGeneratedPaymentDate($installmentsCnabGenerated->installment)),
            $installmentsCnabGenerated->installment->initial_value,
            $installmentsCnabGenerated->installment->fees ==  0 ? '0' : $installmentsCnabGenerated->installment->fees,
            $installmentsCnabGenerated->installment->fine ==  0 ? '0' : $installmentsCnabGenerated->installment->fine,
            $installmentsCnabGenerated->installment->discount ==  0 ? '0' : $installmentsCnabGenerated->installment->discount,
            ExportsUtils::installmentTotalFinalValue($installmentsCnabGenerated->installment),
            $installmentsCnabGenerated->installment->group_payment->title,
            $installmentsCnabGenerated->installment->billet_number,
            ExportsUtils::translatedInstallmentBilletType($installmentsCnabGenerated->installment),
            $installmentsCnabGenerated->installment->bar_code,
            $installmentsCnabGenerated->installment->entity_type ?? '',
            $installmentsCnabGenerated->installment->entity_name ?? '',
            $installmentsCnabGenerated->installment->cpf_cnpj ?? '',
            $installmentsCnabGenerated->installment->bank_account_provider->bank->title ?? '',
            $installmentsCnabGenerated->installment->bank_account_provider->agency_number ?? '',
            $installmentsCnabGenerated->installment->bank_account_provider->agency_check_number ?? '',
            ExportsUtils::translatedInstallmentBankAccountType($installmentsCnabGenerated->bank_account_provider->account_type ?? null),
            $installmentsCnabGenerated->installment->bank_account_provider->account_number ?? '',
            $installmentsCnabGenerated->installment->bank_account_provider->account_check_number ?? '',
            $installmentsCnabGenerated->installment->payment_request->user->name ?? '',
            $installmentsCnabGenerated->installment->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $installmentsCnabGenerated->installment->payment_request->approval->status),
            ExportsUtils::approver($installmentsCnabGenerated->installment->payment_request),
            $installmentsCnabGenerated->installment->note,
        ];
    }

    public function headings(): array
    {
        return [
            'Conta',
            'Parcela',
            'Empresa',
            'CNPJ Empresa',
            'Banco da Empresa',
            'Agência da Empresa',
            'Digíto da Agência da Empresa',
            'Conta da Empresa',
            'Digíto da Conta da Empresa',
            'Convênio da Empresa',
            'Negócio',
            'Plano de Contas',
            'Centro de Custo',
            'VPs do Centro de Custo',
            'Gestores do Centro de Custo',
            'Identificação Fornecedor',
            'Razão Social',
            'Apelido do Fornecedor',
            'Data de Criação',
            'Data de Emissão',
            'Data de Vencimento',
            'Data de Pagamento',
            'Dias de Atraso',
            'Data Aprovação CAP',
            'Analista CAP',
            'Usuário da Geração Arquivo',
            'Data CNAB da Geração Arquivo',
            'Resultado de CNAB Gerado',
            'Pagamento Realizado',
            'Valor Inicial',
            'Juros',
            'Multa',
            'Desconto',
            'Valor a Pagar',
            'Forma de Pagamentos',
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
            'Status',
            'Aprovador',
            'Observação',
        ];
    }
}
