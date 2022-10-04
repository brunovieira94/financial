<?php

namespace App\Exports;

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
        if (array_key_exists('status', $requestInfo) && $requestInfo['status'] == 3) {
            $installment = $installment->with(['payment_request' => function ($query) {
                return $query->withTrashed();
            },]);
        }
        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', 1);
            });
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        if (!array_key_exists('company', $requestInfo))
            return [];

        return $installment->get();
    }

    public function map($installment): array
    {
        return [
            $installment->payment_request->id,
            $installment->parcel_number,
            $installment->payment_request->provider->trade_name ?? '',
            $installment->payment_request->cost_center->title ?? '',
            $installment->due_date,
            $installment->extension_date,
            $installment->competence_date,
            $installment->initial_value,
            $installment->fees,
            $installment->fine,
            $installment->discount,
            $installment->note,
            $installment->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $installment->payment_request->approval->status)
        ];
    }

    public function headings(): array
    {
        return [
            'Conta',
            'Parcela',
            'Fornecedor',
            'Centro de Custo',
            'Data de Pagamento',
            'Data de Prorrogação',
            'Data de Competência',
            'Valor',
            'Juros',
            'Multa',
            'Desconto',
            'Observações',
            'Etapa Atual',
            'Status Atual'
        ];
    }
}
