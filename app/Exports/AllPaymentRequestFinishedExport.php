<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlow;
use App\Services\Utils;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Exports\Utils as ExportsUtils;
use Illuminate\Contracts\Queue\ShouldQueue;

class AllPaymentRequestFinishedExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{
    private $requestInfo;
    private $totalTax;
    private $fileName;

    public function __construct($requestInfo, $fileName){
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
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
        ->where('status', 7)
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
