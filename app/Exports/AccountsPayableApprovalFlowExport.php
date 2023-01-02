<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallments;
use App\Models\UserHasPaymentRequest;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;
use Config;

class AccountsPayableApprovalFlowExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;
    private $filterCanceled = false;
    private $paymentRequestCleanWith = ['installments', 'company', 'provider', 'cost_center', 'approval.approval_flow', 'currency', 'cnab_payment_request.cnab_generated'];

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $approvalFlowUserOrder = ApprovalFlow::where('role_id', auth()->user()->role_id)->get(['order', 'group_approval_flow_id']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $paymentRequest = PaymentRequest::with(['provider', 'company']);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);

        $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
            $arrayStatus = Utils::statusApprovalFlowRequest($requestInfo);
            $query->whereIn('status', $arrayStatus)
                ->where('deleted_at', '=', null);
        });
        $idsPaymentRequestOrder = [];
        foreach ($approvalFlowUserOrder as $approvalOrder) {
            $accountApprovalFlow = AccountsPayableApprovalFlowClean::where('order', $approvalOrder['order'])->with('payment_request');
            $accountApprovalFlow = $accountApprovalFlow->whereHas('payment_request', function ($query) use ($approvalOrder) {
                $query->where('group_approval_flow_id', $approvalOrder['group_approval_flow_id']);
            })->get('payment_request_id');
            $idsPaymentRequestOrder = array_merge($idsPaymentRequestOrder, $accountApprovalFlow->pluck('payment_request_id')->toArray());
        }
        $paymentRequest = $paymentRequest->whereIn('id', $idsPaymentRequestOrder);
        $multiplePaymentRequest = UserHasPaymentRequest::where('user_id', auth()->user()->id)->where('status', 0)->get('payment_request_id');
        //$paymentRequest = $paymentRequest->orWhere(function ($query) use ($multiplePaymentRequest, $requestInfo) {
        $ids = $multiplePaymentRequest->pluck('payment_request_id')->toArray();
        $paymentRequestMultiple = PaymentRequest::withoutGlobalScopes()->whereIn('id', $ids);
        $paymentRequestMultiple = Utils::baseFilterReportsPaymentRequest($paymentRequestMultiple, $requestInfo);
        $paymentRequestMultiple->get('id');
        $ids = $paymentRequestMultiple->pluck('id')->toArray();
        //union ids payment request
        $paymentRequestIDs = $paymentRequest->get('id');
        $paymentRequestIDs = $paymentRequest->pluck('id')->toArray();
        $ids = array_merge($ids, $paymentRequestIDs);
        $paymentRequest = PaymentRequestClean::withoutGlobalScopes()->whereIn('id', $ids)->with($this->paymentRequestCleanWith);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'id';

        return $paymentRequest->get();
    }

    public function map($paymentRequest): array
    {
        $this->totalTax = 0;
        if (isset($paymentRequest->tax)) {
            foreach ($paymentRequest->tax as $value) {
                $this->totalTax += $value['tax_amount'];
            }
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
            Config::get('constants.statusPt.' . $paymentRequest->approval->status),
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
