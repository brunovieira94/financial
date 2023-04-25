<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\PaymentRequestHasInstallments;
use App\Services\Utils;
use Config;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class AllApprovedInstallment implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{
    private $requestInfo;
    private $fileName;

    public function __construct($requestInfo, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $installment = PaymentRequestHasInstallments::with(['payment_request', 'group_payment', 'bank_account_provider', 'bank_account_company', 'group_payment_received']);

        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', 1);
            });
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);
        if (!array_key_exists('company', $requestInfo))
            return collect([]);

        return $installment->get();
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
