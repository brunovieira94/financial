<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallments;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\DB;

class BillsToPayExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, ShouldQueue, WithChunkReading
{
    private $requestInfo;
    private $totalTax;
    private $filterCanceled = false;
    private $fileName;

    public function __construct($requestInfo, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
    }

    use Exportable;

    public function query()
    {
        $requestInfo = $this->requestInfo;
        $query = PaymentRequestClean::query()->with(ExportsUtils::withModelDefaultExport('payment-request'));
        $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        return $query;
    }

    public function map($paymentRequest): array
    {
        return ExportsUtils::exportPaymentRequestData($paymentRequest);
    }

    public function headings(): array
    {
        return ExportsUtils::exportPaymentRequestColumn();
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
