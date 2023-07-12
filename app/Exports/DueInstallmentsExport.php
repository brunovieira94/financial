<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Services\Utils;
use Config;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class DueInstallmentsExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
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
        $query = PaymentRequestHasInstallmentsClean::query();
        $query = $query->with(ExportsUtils::withModelDefaultExport('payment-request-installments'));
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

    public function map($installment): array
    {
        return ExportsUtils::exportInstallmentData($installment);
    }

    public function headings(): array
    {
        return ExportsUtils::exportInstallmentColumn();
    }
}
