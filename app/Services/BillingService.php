<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\PaidBillingInfo;
use App\Models\Hotel;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    public function getAllBilling($requestInfo, $approvalStatus)
    {
        if($approvalStatus == 'billing-all'){
            $billing = Utils::search($this->billing, $requestInfo);
        }
        else{
            $billing = Utils::search($this->billing, $requestInfo)->where('approval_status', array_search($approvalStatus, Utils::$approvalStatus));
        }
        if (array_key_exists('payment_status', $requestInfo)){
            $billing->where('payment_status', $requestInfo['payment_status']);
        }
        if (array_key_exists('status_123', $requestInfo)){
            $billing->where('status_123', $requestInfo['status_123']);
        }
        if (array_key_exists('status_cangooroo', $requestInfo)){
            $billing->whereHas('cangooroo', function ($query) use ($requestInfo) {
                $query->where('status', $requestInfo['status_cangooroo']);
            });
        }
        if (array_key_exists('id_hotel_cangooroo', $requestInfo)){
            $billing->whereHas('cangooroo', function ($query) use ($requestInfo) {
                $query->where('hotel_id', $requestInfo['id_hotel_cangooroo']);
            });
        }
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $billing->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $billing->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $billing->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $billing->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $billing->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $billing->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        return Utils::pagination($billing->with($this->with), $requestInfo);
    }

    public function approve($id)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->approval_status = Config::get('constants.billingStatus.approved');
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
        $billing->approval_status = Config::get('constants.billingStatus.disapproved');
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
        $cangooroo = $cangooroo = $this->cangoorooService->updateCangoorooData($billing['reserve']);
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
