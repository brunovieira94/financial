<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\PaidBillingInfo;
use App\Models\Hotel;
use Config;
use Illuminate\Http\Request;

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
        $billing = Utils::search($this->billing, $requestInfo)->where('approval_status', array_search($approvalStatus, Utils::$approvalStatus));
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
        $cangooroo = $this->cangoorooService->updateCangoorooData($billing['reserve']);
        $billingInfo['payment_status'] = $this->getPaymentStatus($billing, $cangooroo);
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
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
        $billingInfo['payment_status'] = $this->getPaymentStatus($billingInfo, $cangooroo);
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
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
        $billingInfo['payment_status'] = $this->getPaymentStatus($billingInfo, $cangooroo);
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
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
        // id123 deve ser diferente de 0 (implementar q o mesmo não pode ser igual a 0 ao salvar)
        if(($cangooroo['selling_price'] - 5) >= $billingInfo['supplier_value'] || ($cangooroo['selling_price'] + 5) <= $billingInfo['supplier_value']){
            $suggestionReason = $suggestionReason.' | Valor informado diferente do valor no Cangooroo';
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
}
