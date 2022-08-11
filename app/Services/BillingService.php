<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\HotelApprovalFlow;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BillingService
{

    private $billing;
    private $cangoorooService;
    private $approvalFlow;

    private $with = ['user', 'cangooroo', 'reason_to_reject', 'approval_flow'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService, HotelApprovalFlow $approvalFlow)
    {
        $this->billing = $billing;
        $this->cangoorooService = $cangoorooService;
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllBilling($requestInfo, $approvalStatus)
    {
        $billing = Utils::search($this->billing, $requestInfo)->where('approval_status', array_search($approvalStatus, Utils::$approvalStatus));
        if($approvalStatus == 'billing-open')
        {
            $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);
            if (!$approvalFlowUserOrder) return response([], 404);
            $billing->whereIn('order', $approvalFlowUserOrder->toArray())
            ->where('deleted_at', '=', null);
        }
        return Utils::pagination($billing->with($this->with), $requestInfo);
    }

    public function approve($id)
    {
        $billing = $this->billing->findOrFail($id);
        // if ($this->approvalFlow
        //     ->where('order', $billing->order)
        //     ->where('role_id', auth()->user()->role_id)
        //     ->doesntExist()
        // ) {
        //     return response()->json([
        //         'error' => 'Não é permitido a esse usuário aprovar a conta ' . $billing->id . ', modifique o fluxo de aprovação.',
        //     ], 422);
        // }

        $maxOrder = $this->approvalFlow->max('order');
        if ($billing->order >= $maxOrder) {
            $billing->approval_status = Config::get('constants.status.approved');
        } else {
            $billing->order += 1;
        }
        //$billing->approval_status = Config::get('constants.status.approved');
        $billing->reason = null;
        $billing->reason_to_reject_id = null;
        $billing->save();
        return response()->json([
            'Sucesso' => 'Faturamento aprovado',
        ], 200);
    }

    public function reprove($id, Request $request)
    {
        $billing = $this->billing->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');

        // if ($this->approvalFlow
        //     ->where('order', $billing->order)
        //     ->where('role_id', auth()->user()->role_id)
        //     ->doesntExist()
        // ) {
        //     return response()->json([
        //         'error' => 'Não é permitido a esse usuário reprovar a conta ' . $billing->id . ', modifique o fluxo de aprovação.',
        //     ], 422);
        // }

        $billing->approval_status = Config::get('constants.status.disapproved');

        if ($billing->order > $maxOrder) {
            $billing->approval_status = Config::get('constants.status.open');
        } else if ($billing->order != 0) {
            $billing->order -= 1;
        }

        $billing->reason = $request->reason;
        $billing->reason_to_reject_id = $request->reason_to_reject_id;
        $billing->save();
        return response()->json([
            'Sucesso' => 'Faturamento reprovado',
        ], 200);
    }

    public function getBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $cangooroo = $this->cangoorooService->updateCangoorooData($billing['reserve']);
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billing['reserve']);
        $billing->fill($billingInfo)->save();
        return $this->billing->with($this->with)->findOrFail($id);
    }

    public function postBilling($billingInfo)
    {
        $billing = new Billing;
        $billingInfo['user_id'] = auth()->user()->id;
        $billingInfo['approval_status'] =  Config::get('constants.status.open');
        $billingInfo['order'] =  1;
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['reserve']);
        if (is_array($cangooroo) && array_key_exists('error', $cangooroo)) {
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        $billingInfo['cangooroo_booking_id'] = $cangooroo['booking_id'];
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billingInfo['reserve']);
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
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['reserve']);
        if (is_array($cangooroo) && array_key_exists('error', $cangooroo)) {
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        $billingInfo['approval_status'] =  Config::get('constants.status.open');
        $billingInfo['reason'] = null;
        $billingInfo['reason_to_reject_id'] = null;
        $billingInfo['cangooroo_booking_id'] = $cangooroo['booking_id'];
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billingInfo['reserve']);
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

    public function get123Status($hotelId, $reserve)
    {
        $token = $this->get123Token();
        if($token){
            $apiCall = Http::withHeaders([
                'Shared-Id' => '123',
            ])->withToken($token)->get(env('API_123_STATUS_URL', "http://teste31.123milhas.com/api/v3/hotel/booking/status")."/".$hotelId."/".$reserve);
            if ($apiCall->status() != 200) return null; // N.D dados de reserva inválidos na base 123
            $response = $apiCall->json();
            return $response['status'];
        }
        else{
            return null; //erro ao autenticar api 123, contate o suporte
        }
    }

    public function get123Token()
    {
        $apiCall = Http::withHeaders([
            'secret' => env('API_123_SECRET', Config::get('constants.123_secret')),
        ])->get(env('API_123_AUTH_URL', "http://teste31.123milhas.com/api/v3/client/auth"));
        if ($apiCall->status() != 200) return false;
        $response = $apiCall->json();
        return $response['access_token'];
    }
}
