<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlowLog;
use App\Models\PaymentRequestHasInstallments;
use App\Services\Utils;
use Config;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromCollection;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;

class UserApprovalsReportExport implements FromCollection, WithMapping, WithHeadings
{
    private $requestInfo;
    private $filterCanceled = false;
    private $logPaymentRequestWith = ['user', 'payment_request.provider', 'payment_request.cost_center', 'payment_request.approval.approval_flow', 'payment_request.installments', 'payment_request.currency', 'payment_request.cnab_payment_request.cnab_generated'];

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;

        $logPaymentRequest = AccountsPayableApprovalFlowLog::query();
        $logPaymentRequest = $logPaymentRequest->with($this->logPaymentRequestWith);

        if (array_key_exists('user_approval_id', $requestInfo)) {
            $logPaymentRequest = $logPaymentRequest->whereIn('user_id',  (array)$requestInfo['user_approval_id']);
        }

        $logPaymentRequest = $logPaymentRequest->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });

        if (array_key_exists('date_approval', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['date_approval'])) {
                $logPaymentRequest = $logPaymentRequest->where('created_at', '>=', $requestInfo['date_approval']['from']);
            }
            if (array_key_exists('to', $requestInfo['date_approval'])) {
                $logPaymentRequest = $logPaymentRequest->where('created_at', '<=', $requestInfo['date_approval']['to']);
            }
            if (!array_key_exists('to', $requestInfo['date_approval']) && !array_key_exists('from', $requestInfo['date_approval'])) {
                $logPaymentRequest = $logPaymentRequest->whereBetween('created_at', [now(), now()->addMonths(1)]);
            }
        }

        if (array_key_exists('status_approval', $requestInfo)) {
            $logPaymentRequest = $logPaymentRequest->where('type',  $requestInfo['status_approval']);
        }
        return $logPaymentRequest->get();
    }

    public function map($logPaymentRequest): array
    {
        switch ($logPaymentRequest->type) {
            case 'approved':
                $logPaymentRequest->type = 'Aprovou';
                break;
            case 'rejected':
                $logPaymentRequest->type = 'Rejeitou';
                break;
            case 'canceled':
                $logPaymentRequest->type = 'Cancelou';
                break;
            case 'multiple-approval':
                $logPaymentRequest->type = 'Multipla Aprovação';
                break;
            case 'transfer-approval':
                $logPaymentRequest->type = 'Tranferência de Aprovação';
                break;
            case 'created':
                $logPaymentRequest->type = 'Criou';
                break;
            case 'updated':
                $logPaymentRequest->type = 'Atualizou';
                break;
            case 'deleted':
                $logPaymentRequest->type = 'Deletou';
                break;
            default:
                $logPaymentRequest->type = 'default';
        }

        return [
            $logPaymentRequest->payment_request_id,
            $logPaymentRequest->type,
            $logPaymentRequest->payment_request->company->trade_name ?? '',
            $logPaymentRequest->payment_request->provider->trade_name ?? '',
            $logPaymentRequest->payment_request->provider ? ($logPaymentRequest->payment_request->provider->cnpj ? 'CNPJ: ' . $logPaymentRequest->payment_request->provider->cnpj : 'CPF: ' . $logPaymentRequest->payment_request->provider->cpf) : $logPaymentRequest->payment_request->provider,
            $logPaymentRequest->payment_request->cost_center->title ?? '',
            $logPaymentRequest->payment_request->chart_of_accounts->title ?? '',
            $logPaymentRequest->payment_request->currency ? $logPaymentRequest->payment_request->currency->title : $logPaymentRequest->payment_request->currency,
            $logPaymentRequest->payment_request->net_value ?? '',
            $logPaymentRequest->payment_request->business ? $logPaymentRequest->payment_request->business->name : $logPaymentRequest->payment_request->business,
            $logPaymentRequest->payment_request->emission_date ?? '',
            $logPaymentRequest->user->name ?? '',
            $logPaymentRequest->motive ?? '',
            $logPaymentRequest->created_at,
            $logPaymentRequest->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $logPaymentRequest->payment_request->approval->status)
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Ação',
            'Empresa',
            'Fornecedor',
            'CPF/CNPJ Fornecedor',
            'Centro de Custo',
            'Plano de Contas',
            'Moeda',
            'Valor',
            'Negócio',
            'Data Emissão',
            'Usuário',
            'Motivo',
            'Data',
            'Etapa Atual',
            'Status Atual'
        ];
    }
}
