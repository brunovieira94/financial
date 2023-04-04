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
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class InstallmentsPayableExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
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
        $query = $query->with(['cnab_generated_installment', 'payment_request', 'group_payment', 'bank_account_provider', 'bank_account_company', 'group_payment_received']);

        $query = $query->whereHas('payment_request', function ($query) use ($requestInfo) {
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
