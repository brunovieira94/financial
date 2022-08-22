<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
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

        $paymentRequest->whereHas('approval', function ($query) use ($approvalFlowUserOrder) {
            //$query->whereIn('order', $approvalFlowUserOrder->pluck('order')->toArray())
            $query->whereIn('status', [0, 2])
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
        $paymentRequest = $paymentRequest->orWhere(function ($query) use ($multiplePaymentRequest, $requestInfo) {
            $ids = $multiplePaymentRequest->pluck('payment_request_id')->toArray();
            $paymentRequestMultiple = PaymentRequest::withoutGlobalScopes()->whereIn('id', $ids);
            $paymentRequestMultiple = Utils::baseFilterReportsPaymentRequest($paymentRequestMultiple, $requestInfo);
            $paymentRequestMultiple->get('id');
            $ids = $paymentRequestMultiple->pluck('id')->toArray();
            $query->orWhere('id', $ids);
        });
        $paymentRequest = $paymentRequest->with($this->paymentRequestCleanWith);
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
            $paymentRequest->company->company_name,
            $paymentRequest->provider ? ($paymentRequest->provider->cnpj ? 'CNPJ: ' . $paymentRequest->provider->cnpj : 'CPF: ' . $paymentRequest->provider->cpf) : $paymentRequest->provider,
            $paymentRequest->provider ? ($paymentRequest->provider->company_name ? $paymentRequest->provider->company_name : $paymentRequest->provider->full_name) : $paymentRequest->provider,
            $paymentRequest->emission_date,
            $paymentRequest->pay_date,
            $paymentRequest->amount,
            $paymentRequest->net_value,
            $this->totalTax,
            $paymentRequest->chart_of_accounts ? $paymentRequest->chart_of_accounts->title : $paymentRequest->chart_of_accounts,
            $paymentRequest->cost_center ? $paymentRequest->cost_center->title : $paymentRequest->cost_center,
            $paymentRequest->business ? $paymentRequest->business->name : $paymentRequest->business,
            $paymentRequest->currency ? $paymentRequest->currency->title : $paymentRequest->currency,
            $paymentRequest->exchange_rate,
            $paymentRequest->frequency_of_installments,
            $paymentRequest->days_late,
            $paymentRequest->payment_type,
            $paymentRequest->user ? $paymentRequest->user->email : $paymentRequest->user,
            $paymentRequest->invoice_number,
            $paymentRequest->invoice_type,
            $paymentRequest->bar_code,
            $paymentRequest->next_extension_date,
            $paymentRequest->created_at,
            $paymentRequest->note,
            $paymentRequest->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.'.$paymentRequest->approval->status)
        ];
    }

    public function headings(): array
    {
        return [
            'Id',
            'Empresa',
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
