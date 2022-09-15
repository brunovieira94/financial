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
use Carbon\Carbon;

class InstallmentsPayableExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
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
        $requestInfo = $this->requestInfo;
        $query = PaymentRequestHasInstallments::query();
        $query = $query->with(['cnab_generated_installment', 'payment_request', 'group_payment', 'bank_account_provider']);
        if (array_key_exists('status', $requestInfo) && $requestInfo['status'] == 3) {
            $query = $query->with(['payment_request' => function ($query) {
                return $query->withTrashed();
            },]);
        }
        $query = $query->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        return $query->get();
    }

    public function map($query): array
    {
        return [
            $query->payment_request->id,
            $query->parcel_number,
            $query->payment_request->provider->trade_name ?? '',
            $query->payment_request->cost_center->title ?? '',
            $query->due_date,
            $query->extension_date,
            $query->competence_date,
            $query->initial_value,
            $query->fees,
            $query->fine,
            $query->discount,
            $query->note,
            $query->payment_request->approval->approver_stage_first['title'],
            Config::get('constants.statusPt.' . $query->payment_request->approval->status)
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
