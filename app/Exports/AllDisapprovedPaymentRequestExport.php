<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllDisapprovedPaymentRequestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {

        $approvalFlowUserOrder = ApprovalFlow::where('role_id', auth()->user()->role_id);

        $userCostCenter = auth()->user()->cost_center->map(function($e) {
            return $e->id;
        });

        if (!$approvalFlowUserOrder){
            return response([], 404);
        }

        return AccountsPayableApprovalFlow::join("approval_flow", "approval_flow.order", "=", "accounts_payable_approval_flows.order")
        ->select(['accounts_payable_approval_flows.*'])
        ->join("payment_requests", function($join) use ($userCostCenter) {
            $join->on("accounts_payable_approval_flows.payment_request_id", "=", "payment_requests.id")
            ->where(function($q) use ($userCostCenter) {
                if(!$userCostCenter->isEmpty()){
                $q->where(function($query) use ($userCostCenter) {
                    $query->where("approval_flow.filter_cost_center", true)
                    ->whereIn("payment_requests.cost_center_id", $userCostCenter);
                })
                ->orWhere(function($query) {
                    $query->where("approval_flow.filter_cost_center", false);
                });
            }
            });
        })
        ->whereIn('accounts_payable_approval_flows.order', $approvalFlowUserOrder->get('order')->toArray())
        ->where('status', 2)
        ->whereRelation('payment_request', 'deleted_at', '=', null)
        ->with(['payment_request', 'reason_to_reject'])
        ->distinct(['accounts_payable_approval_flows.id'])
        ->get();
    }

    public function map($accountsPayableApprovalFlow): array
    {
        $this->totalTax = 0;
        foreach ($accountsPayableApprovalFlow->payment_request->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $accountsPayableApprovalFlow->payment_request->id,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->cnpj ? 'CNPJ: '.$accountsPayableApprovalFlow->payment_request->provider->cnpj : 'CPF: '. $accountsPayableApprovalFlow->payment_request->provider->cpf) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->company_name ? $accountsPayableApprovalFlow->payment_request->provider->company_name : $accountsPayableApprovalFlow->payment_request->provider->full_name) : $accountsPayableApprovalFlow->payment_request->provider,
            $accountsPayableApprovalFlow->payment_request->emission_date,
            $accountsPayableApprovalFlow->payment_request->pay_date,
            $accountsPayableApprovalFlow->payment_request->amount,
            $accountsPayableApprovalFlow->payment_request->net_value,
            $this->totalTax,
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
            $accountsPayableApprovalFlow->payment_request->next_extension_date,
            $accountsPayableApprovalFlow->payment_request->created_at,
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
