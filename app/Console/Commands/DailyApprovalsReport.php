<?php

namespace App\Console\Commands;

use App\Models\ApprovalFlow;
use App\Models\User;
use App\Services\NotificationService;
use DB;
use Illuminate\Console\Command;

class DailyApprovalsReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:payment-request-approvals-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails daily to those responsible with accounts pending approval';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::with(['cost_center', 'role'])->where('status', 0)->where('daily_notification_accounts_approval_mail', true)->get();

        foreach ($users as $user) {
            $paymentRequests = [];
            $costCenter = [];
            $approvalFlows = ApprovalFlow::where('role_id', $user->role_id)->get();
            foreach ($approvalFlows as $approvalFlow) {
                $paymentRequestDB = DB::table('payment_requests')
                    ->join('accounts_payable_approval_flows as approval', 'payment_requests.id', '=', 'approval.payment_request_id')
                    ->whereIn('approval.status', [0, 2])
                    ->where('approval.group_approval_flow_id', $approvalFlow->group_approval_flow_id)
                    ->where('approval.order', $approvalFlow->order)
                    ->whereNull('payment_requests.deleted_at')
                    ->where(function ($query) use ($user) {
                        if ($user->role->filter_cost_center) {
                            $query->whereIn('payment_requests.cost_center_id', $user->cost_center->pluck('id'));
                        }
                    })
                    ->orderBy('payment_requests.id', 'desc')
                    ->get(['payment_requests.id', 'cost_center_id']);

                if (!empty($paymentRequestDB)) {
                    foreach ($paymentRequestDB->pluck('id')->toArray() as $id) {
                        array_push($paymentRequests, $id);
                    }
                }
            }
            NotificationService::dailyMailPerUser($paymentRequests, [$user->email]);
        }
    }
}
