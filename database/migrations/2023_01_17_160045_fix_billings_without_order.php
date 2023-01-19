<?php

use App\Models\Billing;
use App\Models\HotelApprovalFlow;
use Illuminate\Database\Migrations\Migration;

class FixBillingsWithoutOrder extends Migration
{
    public function up()
    {
        $maxOrder = HotelApprovalFlow::max('order');
        Billing::where('approval_status', 0)->where('order', '>', $maxOrder)->update(['order' => $maxOrder, 'approval_status' => 1]);
    }
}
