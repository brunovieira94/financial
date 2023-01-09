<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\AccountsPayableApprovalFlow;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Config;

class AllPaymentRequestPaidExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $accountsPayableApprovalFlow = AccountsPayableApprovalFlow::with(['payment_request']);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        return $accountsPayableApprovalFlow
        ->where('status', 4)
        ->whereRelation('payment_request', 'deleted_at', '=', null)
        ->get();
    }

    public function map($accountsPayableApprovalFlow): array
    {
        return ExportsUtils::exportPaymentRequestData($accountsPayableApprovalFlow->payment_request);
    }

    public function headings(): array
    {
        return ExportsUtils::exportPaymentRequestColumn();
    }
}
