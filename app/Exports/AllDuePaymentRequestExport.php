<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Config;

class AllDuePaymentRequestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{
    private $requestInfo;
    private $totalTax;
    private $fileName;

    public function __construct($requestInfo, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
    }

    use Exportable;

    public function collection()
    {
        $result = PaymentRequestClean::with(ExportsUtils::withModelDefaultExport('payment-request'));
        $requestInfo = $this->requestInfo;
        $result = Utils::baseFilterReportsPaymentRequest($result, $requestInfo);
        $result = $result->whereHas('installments', function ($query) use ($requestInfo) {
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
        return $result->get();
        //return PaymentRequest::with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'])->get();
    }

    public function map($paymentRequest): array
    {
        return ExportsUtils::exportPaymentRequestData($paymentRequest);
    }

    public function headings(): array
    {
        return ExportsUtils::exportPaymentRequestColumn();
    }
}
