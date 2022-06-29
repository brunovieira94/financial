<?php

namespace App\Services;


use App\Models\Billing;
use Config;
use Illuminate\Http\Request;
use Response;

class BillingService
{

    private $billing;
    private $cangoorooService;

    private $with = ['user', 'cangooroo', 'reason_to_reject', 'approval_flow'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService)
    {
        $this->billing = $billing;
        $this->cangoorooService = $cangoorooService;
    }

    public function getAllBilling($requestInfo)
    {
        if (!array_key_exists('approval_status', $requestInfo)) {
            return Response()->json(['error' => 'approval_status is required'], 400);
        }
        $billing = Utils::search($this->billing, $requestInfo)->where('approval_status', $requestInfo['approval_status']);
        return Utils::pagination($billing->with($this->with), $requestInfo);
    }

    public function approve($id)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->approval_status = Config::get('constants.status.approved');
        $billing->reason = null;
        $billing->reason_to_reject_id = null;
        $billing->save();
        return response()->json([
            'Sucesso' => 'Pedido aprovado',
        ], 200);
    }

    public function reprove($id, Request $request)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->approval_status = Config::get('constants.status.disapproved');
        $billing->reason = $request->reason;
        $billing->reason_to_reject_id = $request->reason_to_reject_id;
        $billing->save();
        return response()->json([
            'Sucesso' => 'Pedido reprovado',
        ], 200);
    }

    public function getBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $this->cangoorooService->updateCangoorooData($billing['cangooroo_booking_id'], $billing['reserve']);
        return $this->billing->with($this->with)->findOrFail($id);
    }

    public function postBilling($billingInfo)
    {
        $billing = new Billing;
        $billingInfo['user_id'] = auth()->user()->id;
        $billingInfo['approval_status'] =  Config::get('constants.status.open');
        $billingInfo['order'] =  1;
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['cangooroo_booking_id'], $billingInfo['reserve']);
        if (is_array($cangooroo) && array_key_exists('error', $cangooroo)) {
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        $billing = $billing->create($billingInfo);
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function putBilling($id, $billingInfo)
    {
        $billing = $this->billing->findOrFail($id);
        if ($billing->approval_status == Config::get('constants.status.canceled')) {
            return response()->json([
                'error' => 'Pedido previamente cancelado',
            ], 422);
        }
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['cangooroo_booking_id'], $billingInfo['reserve']);
        if (is_array($cangooroo) && array_key_exists('error', $cangooroo)) {
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        $billingInfo['approval_status'] =  Config::get('constants.status.open');
        $billingInfo['reason'] = null;
        $billingInfo['reason_to_reject_id'] = null;
        $billing->fill($billingInfo)->save();
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function deleteBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->approval_status =  Config::get('constants.status.canceled');
        $billing->save();
        return true;
    }
}
