<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequestHasInstallments;
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

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $approvalFlowUserOrder = ApprovalFlow::where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $accountsPayableApprovalFlow = AccountsPayableApprovalFlow::whereIn('order', $approvalFlowUserOrder->toArray())
            ->whereIn('status', [0, 2])
            ->whereRelation('payment_request', 'deleted_at', '=', null)
            ->with(['payment_request', 'approval_flow', 'reason_to_reject']);

        $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            if (array_key_exists('provider', $requestInfo)) {
                $query->where('provider_id', $requestInfo['provider']);
            }
            if (array_key_exists('company', $requestInfo)) {
                $query->where('company_id', $requestInfo['company']);
            }
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->where('cost_center_id', $requestInfo['cost_center']);
            }
            if (array_key_exists('cpfcnpj', $requestInfo)) {
                $query->whereHas('provider', function ($query) use ($requestInfo) {
                    $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
                });
            }
            if (array_key_exists('chart_of_accounts', $requestInfo)) {
                $query->where('chart_of_account_id', $requestInfo['chart_of_accounts']);
            }
            if (array_key_exists('payment_request', $requestInfo)) {
                $query->where('id', $requestInfo['payment_request']);
            }
            if (array_key_exists('user', $requestInfo)) {
                $query->where('user_id', $requestInfo['user']);
            }
            if (array_key_exists('created_at', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['created_at'])) {
                    $query->where('created_at', '>=', $requestInfo['created_at']['from']);
                }
                if (array_key_exists('to', $requestInfo['created_at'])) {
                    $query->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
                }
                if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                    $query->whereBetween('created_at', [now()->addMonths(-1), now()]);
                }
            }
            if (array_key_exists('pay_date', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['pay_date'])) {
                    $query->where('pay_date', '>=', $requestInfo['pay_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['pay_date'])) {
                    $query->where('pay_date', '<=', $requestInfo['pay_date']['to']);
                }
                if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                    $query->whereBetween('pay_date', [now(), now()->addMonths(1)]);
                }
            }
            if (array_key_exists('extension_date', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['extension_date'])) {
                    $installments = PaymentRequestHasInstallments::where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->where('extension_date', '>=', $requestInfo['extension_date']['from'])->get('payment_request_id');
                    $paymentIds = [];
                    $paymentIdsToReturn = [];
                    foreach ($installments as $installment) {
                        if (!in_array($installment->payment_request_id, $paymentIds)) {
                            array_push($paymentIds, $installment->payment_request_id);
                        }
                    }
                    foreach ($paymentIds as $id) {
                        $payment = PaymentRequestHasInstallments::where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->where('payment_request_id', $id)->get()->sortBy('due_date')->first();
                        if ($payment->extension_date >= $requestInfo['extension_date']['from']) {
                            array_push($paymentIdsToReturn, $payment->payment_request_id);
                        }
                    }
                    $query->whereIn('id', $paymentIdsToReturn);
                    // $query->whereHas('installments', function ($query) use ($requestInfo) {
                    //     $query->where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                    // });
                }
                if (array_key_exists('to', $requestInfo['extension_date'])) {
                    $installments = PaymentRequestHasInstallments::where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->where('extension_date', '<=', $requestInfo['extension_date']['to'])->get('payment_request_id');
                    $paymentIds = [];
                    $paymentIdsToReturn = [];
                    foreach ($installments as $installment) {
                        if (!in_array($installment->payment_request_id, $paymentIds)) {
                            array_push($paymentIds, $installment->payment_request_id);
                        }
                    }
                    foreach ($paymentIds as $id) {
                        $payment = PaymentRequestHasInstallments::where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->where('payment_request_id', $id)->get()->sortBy('due_date')->first();
                        if ($payment->extension_date <= $requestInfo['extension_date']['to']) {
                            array_push($paymentIdsToReturn, $payment->payment_request_id);
                        }
                    }
                    $query->whereIn('id', $paymentIdsToReturn);
                }
                if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                    $installments = PaymentRequestHasInstallments::where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->whereBetween('extension_date', [now(), now()->addMonths(1)])->get('payment_request_id');
                    $paymentIds = [];
                    $paymentIdsToReturn = [];
                    foreach ($installments as $installment) {
                        if (!in_array($installment->payment_request_id, $paymentIds)) {
                            array_push($paymentIds, $installment->payment_request_id);
                        }
                    }
                    foreach ($paymentIds as $id) {
                        $payment = PaymentRequestHasInstallments::where('status', '<>', Config::get('constants.status.paid out'))->orWhereNull('status')->where('payment_request_id', $id)->get()->sortBy('due_date')->first();
                        if ($payment->extension_date <= now()->addMonths(1) && now() <= $payment->extension_date) {
                            array_push($paymentIdsToReturn, $payment->payment_request_id);
                        }
                    }
                    $query->whereIn('id', $paymentIdsToReturn);
                }
            }
            if (array_key_exists('days_late', $requestInfo)) {
                $query->whereHas('installments', function ($query) use ($requestInfo) {
                    $query->where('status', '!=', Config::get('constants.status.paid out'))->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($requestInfo['days_late']));
                });
            }
        });

        if (array_key_exists('approval_order', $requestInfo)) {
            $accountsPayableApprovalFlow->where('order', $requestInfo['approval_order']);
        }

        if (array_key_exists('status', $requestInfo)) {
            $accountsPayableApprovalFlow->where('status', $requestInfo['status']);
            if ($requestInfo['status'] == 3) {
                $this->filterCanceled = true;
            }
        }

        if ($this->filterCanceled) {
            $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->withTrashed();
                $query->where('deleted_at', '!=', NULL);
            });
        }

        return $accountsPayableApprovalFlow->get();
    }

    public function map($accountsPayableApprovalFlow): array
    {
        $this->totalTax = 0;
        if (isset($accountsPayableApprovalFlow->payment_request) && isset($accountsPayableApprovalFlow->payment_request->tax)) {
            foreach ($accountsPayableApprovalFlow->payment_request->tax as $value) {
                $this->totalTax += $value['tax_amount'];
            }
        }

        return [
            $accountsPayableApprovalFlow->payment_request->id,
            $accountsPayableApprovalFlow->payment_request->provider ? ($accountsPayableApprovalFlow->payment_request->provider->cnpj ? 'CNPJ: ' . $accountsPayableApprovalFlow->payment_request->provider->cnpj : 'CPF: ' . $accountsPayableApprovalFlow->payment_request->provider->cpf) : $accountsPayableApprovalFlow->payment_request->provider,
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
            $accountsPayableApprovalFlow->payment_request->note,
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
            'Observações',
        ];
    }
}
