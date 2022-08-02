<?php

namespace App\Exports;

use App\Models\PaymentRequestHasInstallments;
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
        });

        return $query->get();
    }

    public function map($query): array
    {
        return [
            $query->payment_request->id,
            $query->parcel_number,
            $query->payment_request->provider->trade_name ?? '',
            $query->cost_center ? $query->cost_center->title : $query->cost_center,
            $query->due_date,
            $query->extension_date,
            $query->competence_date,
            $query->initial_value,
            $query->fees,
            $query->fine,
            $query->discount,
            $query->portion_amount,
            $query->note,
            $query->payment_request->approval->approval_flow ? $query->payment_request->approval->approval_flow->role->title : '',
            Config::get('constants.statusPt.'.$query->payment_request->approval->status)
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
            'Valor Final',
            'Observações',
            'Etapa Atual',
            'Status Atual'
        ];
    }
}
