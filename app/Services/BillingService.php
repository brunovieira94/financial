<?php

namespace App\Services;


use App\Models\Billing;
use Config;
use Illuminate\Http\Request;

class BillingService
{

    private $billing;
    private $cangoorooService;

    private $with = ['user', 'cangooroo', 'reason_to_reject'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService)
    {
        $this->billing = $billing;
        $this->cangoorooService = $cangoorooService;
    }

    public function getAllBilling($requestInfo)
    {
        $billing = Utils::search($this->billing, $requestInfo);
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
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['cangooroo_booking_id'], $billingInfo['reserve']);
        if (is_array($cangooroo) && array_key_exists('error', $cangooroo)) {
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        $billing->fill($billingInfo)->save();
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function deleteBilling($id)
    {
        $this->billing->findOrFail($id)->delete();
        return true;
    }
}
