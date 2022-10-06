<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\PaidBillingInfo;
use App\Models\BankAccount;
use App\Models\Hotel;
use App\Models\HotelApprovalFlow;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BillingService
{

    private $billing;
    private $cangoorooService;
    private $approvalFlow;

    private $with = ['bank_account', 'user', 'cangooroo', 'reason_to_reject', 'approval_flow'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService, HotelApprovalFlow $approvalFlow)
    {
        $this->billing = $billing;
        $this->cangoorooService = $cangoorooService;
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllBilling($requestInfo, $approvalStatus)
    {
        if ($approvalStatus == 'billing-all') {
            $billing = Utils::search($this->billing, $requestInfo);
        } else {
            $billing = Utils::search($this->billing, $requestInfo)->where('approval_status', array_search($approvalStatus, Utils::$approvalStatus));
        }
        $billing = Utils::baseFilterBilling($billing, $requestInfo);
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
            $billing->approval_status = Config::get('constants.billingStatus.approved');
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

        $billing->approval_status = Config::get('constants.billingStatus.disapproved');

        if ($billing->order > $maxOrder) {
            $billing->approval_status = Config::get('constants.billingStatus.open');
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
        $billingInfo['payment_status'] = $this->getPaymentStatus($billing, $cangooroo);
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billing['reserve']);
        $billingSuggestion = $this->getBillingSuggestion($billing, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        $billing->fill($billingInfo)->save();
        return $this->billing->with($this->with)->findOrFail($id);
    }

    public function postBilling($billingInfo)
    {
        $billingInfo['user_id'] = auth()->user()->id;
        $billingInfo['approval_status'] =  Config::get('constants.billingStatus.open');
        $billingInfo['order'] =  1;
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['reserve'], $billingInfo['cangooroo_booking_id'], $billingInfo['cangooroo_service_id']);
        if (is_array($cangooroo) && (array_key_exists('error', $cangooroo) || array_key_exists('invalid_hotel', $cangooroo))) {
            return response()->json([
                'error' => array_key_exists('error', $cangooroo) ? $cangooroo['error'] : $cangooroo['invalid_hotel'],
            ], 422);
        }
        //$cangooroo = Cangooroo::where('service_id', $billingInfo['cangooroo_service_id'])->first();
        //$billingInfo['cangooroo_booking_id'] = $cangooroo['booking_id'];
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billingInfo['reserve']);
        $billingInfo['payment_status'] = $this->getPaymentStatus($billingInfo, $cangooroo);
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        if(array_key_exists('preview', $billingInfo) && $billingInfo['preview']){
            return [
                'status_123' => $billingInfo['status_123'],
                'payment_status' => $billingInfo['payment_status'],
                'suggestion' => $billingInfo['suggestion'],
                'suggestion_reason' => $billingInfo['suggestion_reason'],
            ];
        }
        if(array_key_exists('bank_account', $billingInfo))
        {
            $bankAccount = new BankAccount;
            $bankAccount = $bankAccount->create($billingInfo['bank_account']);
            $billingInfo['bank_account_id'] = $bankAccount->id;
        }
        $billing = new Billing;
        $billing = $billing->create($billingInfo);
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function putBilling($id, $billingInfo)
    {
        $billing = $this->billing->findOrFail($id);
        if ($billing->approval_status == Config::get('constants.billingStatus.canceled')) {
            return response()->json([
                'error' => 'Pedido previamente cancelado',
            ], 422);
        }
        $cangooroo = $this->cangoorooService->updateCangoorooData($billingInfo['reserve'], $billingInfo['cangooroo_booking_id'], $billingInfo['cangooroo_service_id']);
        if (is_array($cangooroo) && (array_key_exists('error', $cangooroo) || array_key_exists('invalid_hotel', $cangooroo))) {
            return response()->json([
                'error' => array_key_exists('error', $cangooroo) ? $cangooroo['error'] : $cangooroo['invalid_hotel'],
            ], 422);
        }
        $billingInfo['approval_status'] =  Config::get('constants.billingStatus.open');
        $billingInfo['reason'] = null;
        $billingInfo['reason_to_reject_id'] = null;
        //$cangooroo = Cangooroo::where('service_id', $billingInfo['cangooroo_service_id'])->first();
        // $billingInfo['cangooroo_booking_id'] = $cangooroo['booking_id'];
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billingInfo['reserve']);
        $billingInfo['payment_status'] = $this->getPaymentStatus($billingInfo, $cangooroo);
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        if(array_key_exists('preview', $billingInfo) && $billingInfo['preview']){
            return [
                'status_123' => $billingInfo['status_123'],
                'payment_status' => $billingInfo['payment_status'],
                'suggestion' => $billingInfo['suggestion'],
                'suggestion_reason' => $billingInfo['suggestion_reason'],
            ];
        }
        if(array_key_exists('bank_account', $billingInfo))
        {
            $bankAccount = BankAccount::where('id', $billing['bank_account_id'])->first();
            if($bankAccount) $bankAccount->fill($billingInfo['bank_account'])->save();
            else{
                $bankAccount = new BankAccount;
                $bankAccount = $bankAccount->create($billingInfo['bank_account']);
                $billingInfo['bank_account_id'] = $bankAccount->id;
            }
        }
        $billing->fill($billingInfo)->save();
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function deleteBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->approval_status =  Config::get('constants.billingStatus.canceled');
        $billing->save();
        return true;
    }

    public function getPaymentStatus($billing, $cangooroo)
    {
        $reserve = $billing['reserve'];
        $paidReserves = PaidBillingInfo::where('reserve', $reserve)->get();
        if(empty($paidReserves->toArray())){
            return "Não Pago";
        }
        else{
            $sum = 0;
            foreach ($paidReserves as $paidReserve) {
                $sum += $paidReserve['supplier_value'];
            }
            if($sum >= ($cangooroo['selling_price']-5)) return "Pago";
            else return "Pago - Parcial";
        }
    }

    public function getBillingSuggestion($billingInfo, $cangooroo)
    {
        $suggestionReason = '';
        if($billingInfo['payment_status'] != 'Não Pago'){
            $suggestionReason = $suggestionReason.' | Reserva deve estar em aberto';
        }
        if($cangooroo['status'] != 'Confirmed'){
            $suggestionReason = $suggestionReason.' | Reserva não confirmada no Cangooroo';
        }
        if($billingInfo['status_123'] != 'Emitida' && $billingInfo['status_123'] != 'Emitido'){
            $suggestionReason = $suggestionReason.' | Reserva não emitida no Admin';
        }
        if($this->billing->where('reserve', $billingInfo['reserve'])->where('cangooroo_service_id', $billingInfo['cangooroo_service_id'])->whereIn('approval_status', [0,1])->first()){
            $suggestionReason = $suggestionReason.' | Reserva cadastrada em duplicidade';
        }
        // id123 deve ser diferente de 0 (implementar q o mesmo não pode ser igual a 0 ao salvar)
        if(($cangooroo['selling_price'] - 5) >= $billingInfo['supplier_value'] || ($cangooroo['selling_price'] + 5) <= $billingInfo['supplier_value']){
            $suggestionReason = $suggestionReason.' | Valor informado diferente do valor no Cangooroo';
        }
        if(!$cangooroo->hotel->is_valid){
            $suggestionReason = $suggestionReason.' | Hotel não validado';
        }
        // tipo de faturamento deve ser diferente de vcn
        // $hotel = Hotel::where('id_hotel_cangooroo', $cangooroo['hotel_id'])->first();
        // if($hotel['billing_type'] == 2){
        //     $suggestionReason = $suggestionReason.' | Tipo de faturamento deve ser diferente de VCN';
        // }
        if($suggestionReason == ''){
            $suggestion = true;
        }
        else{
            $suggestion = false;
            $suggestionReason = substr_replace($suggestionReason, '', 0, 3);
        }
        return [
            'suggestion' => $suggestion,
            'suggestion_reason' => $suggestionReason
        ];
    }

    public function get123Status($hotelId, $reserve)
    {
        $token = $this->get123Token();
        if($token){
            $apiCall = Http::withHeaders([
                'Shared-Id' => '123',
            ])->withToken($token)->get(env('API_123_STATUS_URL', "http://teste33.123milhas.com/api/v3/hotel/booking/status")."/".$hotelId."/".$reserve);
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
        ])->get(env('API_123_AUTH_URL', "http://teste33.123milhas.com/api/v3/client/auth"));
        if ($apiCall->status() != 200) return false;
        $response = $apiCall->json();
        return $response['access_token'];
    }

    public function refreshStatuses($id)
    {
        $billingInfo = [];
        $billing = $this->billing->findOrFail($id);
        $cangooroo = $this->cangoorooService->updateCangoorooData($billing['reserve'], $billing['cangooroo_booking_id'], $billing['cangooroo_service_id']);
        if (is_array($cangooroo) && (array_key_exists('error', $cangooroo) || array_key_exists('invalid_hotel', $cangooroo))) {
            return response()->json([
                'error' => array_key_exists('error', $cangooroo) ? $cangooroo['error'] : $cangooroo['invalid_hotel'],
            ], 422);
        }
        $status['cangooroo'] = $cangooroo['status'];
        $billingInfo['payment_status'] = $this->getPaymentStatus($billing, $cangooroo);
        $billingInfo['status_123'] = $this->get123Status($cangooroo['hotel_id'],$billing['reserve']);
        $billingSuggestion = $this->getBillingSuggestion($billing, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        $billing->fill($billingInfo)->save();
        $billingInfo['cangooroo'] = $cangooroo['status'];
        return $billingInfo;
    }
}
