<?php

namespace App\Exports;

use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\Hotel;
use App\Models\BankAccount;
use App\Models\HotelApprovalFlow;
use App\Models\HotelReasonToReject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\Utils;
use Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Exports\Utils as ExportsUtils;

class BillingForApprovalExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{

    use Exportable;

    private $requestInfo;
    private $fileName;

    public function __construct($requestInfo, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
    }

    public function collection()
    {
        $approvalFlowUserOrders = HotelApprovalFlow::where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrders)
            return response([], 404);

        $query = Billing::query()->with(['cangooroo.hotel.bank_account', 'approval_flow', 'user', 'reason_to_reject', 'bank_account']);
        $query = Utils::baseFilterBilling($query, $this->requestInfo);

        $query = $query->whereIn('approval_status', [0, 2])->where('deleted_at', '=', null);

        $billingIDs = [];
        foreach ($approvalFlowUserOrders as $approvalFlowOrder) {
            $billingApprovalFlow = Billing::where('order', $approvalFlowOrder['order']);
            $billingIDs = array_merge($billingIDs, $billingApprovalFlow->pluck('id')->toArray());
        }
        $query = $query->whereIn('id', $billingIDs);
        return $query->get();
    }

    public function map($billing): array
    {
        return ExportsUtils::exportBillingData($billing);
    }

    public function headings(): array
    {
        return ExportsUtils::exportBillingColumn();
    }
}
