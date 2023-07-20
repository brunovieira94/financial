<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\Export;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromCollection;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;
use Storage;

class PaymentRequestExportQueue implements FromCollection, WithMapping, WithHeadings
{
    private $paymentRequestClean;

    public function __construct($paymentRequestClean)
    {
        $this->paymentRequestClean = $paymentRequestClean;
    }

    use Exportable;

    public function collection()
    {
        return $this->paymentRequestClean;
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
