<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\BillingPayment;
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
    private $billingPayment;

    private $with = ['bank_account', 'user', 'cangooroo', 'reason_to_reject', 'approval_flow', 'billing_payment'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService, HotelApprovalFlow $approvalFlow, BillingPayment $billingPayment)
    {
        $this->billing = $billing;
        $this->cangoorooService = $cangoorooService;
        $this->approvalFlow = $approvalFlow;
        $this->billingPayment = $billingPayment;
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
            $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
            if($billingPayment){
                $this->openOrApprovePaymentBilling($billingPayment, $billing);
            }
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

    public function approveMany($requestInfo)
    {
        if (array_key_exists('ids', $requestInfo)) {
            if (array_key_exists('reprove', $requestInfo) && $requestInfo['reprove'] == true) {
                foreach ($requestInfo['ids'] as $value) {
                    $billing = $this->billing->findOrFail($value);
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

                    $billing->reason = null;
                    $billing->save();
                }
                return response()->json([
                    'Sucesso' => 'Faturamentos reprovados',
                ], 200);
            } else {
                foreach ($requestInfo['ids'] as $value) {
                    $billing = $this->billing->findOrFail($value);
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
                        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
                        if($billingPayment){
                            $this->openOrApprovePaymentBilling($billingPayment, $billing);
                        }
                    } else {
                        $billing->order += 1;
                    }
                    //$billing->approval_status = Config::get('constants.status.approved');
                    $billing->reason = null;
                    $billing->reason_to_reject_id = null;
                    $billing->save();
                }
                return response()->json([
                    'Sucesso' => 'Faturamentos aprovados',
                ], 200);
            }
        } else {
            return response()->json([
                'error' => 'Nenhum faturamento selecionado',
            ], 422);
        }
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

        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
        if($billingPayment){
            $billingPayment->status = Config::get('constants.billingStatus.open');
            $billingPayment->save();
        }
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
        $cangooroo = $this->cangoorooService->updateCangoorooData($billing['reserve'], $billing['cangooroo_booking_id'], $billing['cangooroo_service_id']);
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
        $billingInfo['billing_payment_id'] = $this->syncBillingPayment($billingInfo, $cangooroo);
        if(!$billingInfo['billing_payment_id']){
            return response()->json([
                'error' => 'Existem divergências para esse Código de Boleto',
            ], 422);
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
        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
        if($billingPayment){
            $billingPayment->status = Config::get('constants.billingStatus.open');
            $billingPayment->save();
        }
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
        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
        if($billingPayment){
            if(count($billingPayment->billings) <= 1) $billingPayment->delete();
            else{
                $this->openOrApprovePaymentBilling($billingPayment, $billing);
            }
        }
        $billing->billing_payment_id = null;
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
        $cancellationValueToUse = 0;
        if($billingInfo['payment_status'] != 'Não Pago'){
            $suggestionReason = $suggestionReason.' | Reserva deve estar em aberto';
        }
        if($billingInfo['status_123'] != 'Emitida' && $billingInfo['status_123'] != 'Emitido'){
            $suggestionReason = $suggestionReason.' | Reserva não emitida no Admin';
        }
        if($this->billing->where('reserve', $billingInfo['reserve'])->where('cangooroo_service_id', $billingInfo['cangooroo_service_id'])->whereIn('approval_status', [0,1])->first()){
            $suggestionReason = $suggestionReason.' | Reserva cadastrada em duplicidade';
        }
        if($cangooroo['status'] == 'Cancelled'){
            $cancellationDate = (!$cangooroo['cancellation_date'] || strtotime($cangooroo['cancellation_date']) <= 1) ? $cangooroo['last_update'] : $cangooroo['cancellation_date'];
            $cancellationStartDate = (strtotime($cangooroo['check_in']) > strtotime("+5 days",strtotime($cangooroo['reservation_date']))) ? strtotime("+3 days",strtotime($cangooroo['cancellation_policies_start_date'])) : strtotime($cangooroo['cancellation_policies_start_date']);
            if(strtotime($cancellationDate) > strtotime($cangooroo['check_in'])){
                $cancellationValueToUse = $cangooroo['selling_price'];
            }
            else if(strtotime($cancellationDate) > $cancellationStartDate){
                $cancellationValueToUse = $cangooroo['cancellation_policies_value'];
            }
            if($cancellationValueToUse != $billingInfo['supplier_value']){
                $suggestionReason = $suggestionReason.' | Valor informado diferente do valor de Cancelamento: R$ '.$cancellationValueToUse;
            }
        }
        else{
            if($cangooroo['status'] != 'Confirmed'){
                $suggestionReason = $suggestionReason.' | Reserva não confirmada no Cangooroo';
            }
            // if(($cangooroo['selling_price'] - 5) >= $billingInfo['supplier_value'] || ($cangooroo['selling_price'] + 5) <= $billingInfo['supplier_value']){
            if($cangooroo['selling_price'] != $billingInfo['supplier_value']){
                $suggestionReason = $suggestionReason.' | Valor informado diferente do valor no Cangooroo';
            }
        }
        if(!$cangooroo->hotel->is_valid){
            $suggestionReason = $suggestionReason.' | Hotel não validado';
        }
        if($cangooroo->hotel->cpf_cnpj != $billingInfo['cnpj'] && $cangooroo->hotel->cnpj_extra != $billingInfo['cnpj']){
            $suggestionReason = $suggestionReason.' | Cnpj do Titular diferente dos CNPJ cadastrados para esse hotel';
        }
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
            ])->withToken($token)->get(env('API_123_STATUS_URL', "https://api.123milhas.com/api/v3.1/hotel/booking/status/").$hotelId."/".$reserve);
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
        ])->get(env('API_123_AUTH_URL', "https://api.123milhas.com/api/v3.1/client/auth"));
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

    public function syncBillingPayment($billingInfo, $cangooroo)
    {
        $fields = ['pay_date', 'recipient_name', 'oracle_protocol', 'cnpj', 'hotel_id'];
        $billingInfo['hotel_id'] = $cangooroo['hotel_id'];
        if(!is_null($billingInfo['boleto_code'])){
            $findBillingPayment = BillingPayment::where('boleto_code', $billingInfo['boleto_code'])->where('status', 0)->first();
            if($findBillingPayment){
                foreach ($fields as $field) {
                    if($field == 'pay_date'){
                        if(strtotime($billingInfo[$field]) != strtotime($findBillingPayment[$field])) return false;
                    }
                    else if($billingInfo[$field] != $findBillingPayment[$field]) return false;
                }
                $findBillingPayment->status = Config::get('constants.billingStatus.open');
                $findBillingPayment->save();
                return $findBillingPayment->id;
            }
            else{
                $billingPayment = new BillingPayment();
                $billingPayment = $billingPayment->create($billingInfo);
                return $billingPayment->id;
            }
        }
        else{
            $billingPayment = new BillingPayment();
            $billingPayment = $billingPayment->create($billingInfo);
            return $billingPayment->id;
        }
    }

    public function openOrApprovePaymentBilling($billingPayment, $billing)
    {
        $billingPayment->status = Config::get('constants.billingStatus.approved');
        foreach($billingPayment->billings as $value){
            if($value->approval_status != Config::get('constants.billingStatus.approved') && $value->id != $billing->id){
                $billingPayment->status = Config::get('constants.billingStatus.open');
            }
        }
        $billingPayment->save();
    }
}
