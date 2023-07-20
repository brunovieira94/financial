<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\Export;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromCollection;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Storage;

class PaymentRequestHasInstalmentExportQueue implements FromCollection, WithMapping, WithHeadings
{
    private $paymentRequestInstallmentClean;

    public function __construct($paymentRequestInstallmentClean)
    {
        $this->paymentRequestInstallmentClean = $paymentRequestInstallmentClean;
    }

    use Exportable;

    public function collection()
    {
        return $this->paymentRequestInstallmentClean;
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
