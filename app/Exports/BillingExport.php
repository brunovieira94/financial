<?php

namespace App\Exports;

use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\Hotel;
use App\Models\BankAccount;
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

class BillingExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{

    use Exportable;

    private $requestInfo;
    private $approvalStatus;
    private $fileName;

    public function __construct($requestInfo, $approvalStatus, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->approvalStatus = $approvalStatus;
        $this->fileName = $fileName;
    }

    public function collection()
    {
        $query = Billing::query()->with(['cangooroo.hotel.bank_account', 'approval_flow', 'user', 'reason_to_reject', 'bank_account']);
        if ($this->approvalStatus != 'billing-all') {
            $query = $query->where('approval_status', array_search($this->approvalStatus, Utils::$approvalStatus));
        }
        $query = Utils::baseFilterBilling($query, $this->requestInfo);
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
