<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\BillingPayment;
use App\Models\PaidBillingInfo;
use App\Models\BankAccount;
use App\Models\Hotel;
use App\Models\HotelApprovalFlow;
use App\Models\HotelReasonToReject;
use App\Models\User;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\reports\RouteBillingResource;
use App\Models\BillingHasAttachments;

class BillingService
{

    private $billing;
    private $cangoorooService;
    private $approvalFlow;
    private $billingPayment;
    private $attachments;

    private $with = ['bank_account', 'user', 'cangooroo', 'attachments', 'reason_to_reject', 'approval_flow', 'billing_payment'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService, HotelApprovalFlow $approvalFlow, BillingPayment $billingPayment, BillingHasAttachments $attachments)
    {
        $this->billing = $billing;
        $this->cangoorooService = $cangoorooService;
        $this->approvalFlow = $approvalFlow;
        $this->billingPayment = $billingPayment;
        $this->attachments = $attachments;
    }

    public function getAllBilling($requestInfo, $approvalStatus)
    {
        $billing = Utils::search($this->billing, $requestInfo);
        if ($approvalStatus != 'billing-all') {
            $billing = $billing->where('approval_status', array_search($approvalStatus, Utils::$approvalStatus));
        }
        $billing = Utils::baseFilterBilling($billing, $requestInfo);
        $requestInfo['perPage'] = $requestInfo['perPage'] ?? 200;
        return RouteBillingResource::collection(Utils::pagination($billing->with(['approval_flow', 'cangooroo', 'billing_payment']), $requestInfo));
    }

    public function getAllBillingsForApproval($requestInfo)
    {
        $approvalFlowUserOrders = $this->approvalFlow->where('role_id', auth()->user()->role_id)->pluck('order')->toArray();

        $billing = Utils::search($this->billing, $requestInfo);
        $billing = Utils::baseFilterBilling($billing, $requestInfo);

        $billing = $billing->whereIn('order', $approvalFlowUserOrders)->whereIn('approval_status', [0, 2])->where('deleted_at', '=', null);

        // $billingIDs = [];
        // foreach ($approvalFlowUserOrders as $approvalFlowOrder) {
        //     $billingApprovalFlow = $this->billing->where('order', $approvalFlowOrder['order']);
        //     $billingIDs = array_merge($billingIDs, $billingApprovalFlow->pluck('id')->toArray());
        // }
        // $billing = $billing->whereIn('id', $billingIDs);

        $requestInfo['perPage'] = $requestInfo['perPage'] ?? 200;
        return RouteBillingResource::collection(Utils::pagination($billing->with(['approval_flow', 'cangooroo', 'billing_payment']), $requestInfo));
    }

    public function approveAll($requestInfo)
    {
        $approvalFlowUserOrders = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrders)
            return response([], 404);

        $billing = Utils::search($this->billing, $requestInfo);
        $billing = Utils::baseFilterBilling($billing, $requestInfo);

        $billing = $billing->whereIn('approval_status', [0, 2])->where('deleted_at', '=', null);

        $billingIDs = [];
        foreach ($approvalFlowUserOrders as $approvalFlowOrder) {
            $billingApprovalFlow = $this->billing->where('order', $approvalFlowOrder['order']);
            $billingIDs = array_merge($billingIDs, $billingApprovalFlow->pluck('id')->toArray());
        }
        $billing = $billing->whereIn('id', $billingIDs);

        $billings = $billing->get();
        $billingPayments = [];
        $maxOrder = $this->approvalFlow->max('order');
        $arrayOrder = $this->approvalFlow
            ->where('role_id', auth()->user()->role_id)
            ->pluck('order')->toArray();
        foreach ($billings as $billing) {
            $stage = 0;
            if (!in_array($billing->order, $arrayOrder)) {
                DB::rollback();
                return response()->json([
                    'error' => 'Não é permitido a esse usuário aprovar a conta ' . $billing->id . ', modifique o fluxo de aprovação.',
                ], 422);
            }

            if ($billing->order >= $maxOrder) {
                $billing->approval_status = Config::get('constants.billingStatus.approved');
                if ($billing->billing_payment_id && !in_array($billing->billing_payment_id, $billingPayments)) {
                    $billingPayments[] = $billing->billing_payment_id;
                }
                // $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
                // // push $billing->billing_payment_id if !array_key_exists
                // if($billingPayment){
                //     $this->openOrApprovePaymentBilling($billingPayment, $billing);
                // }
                $stage = $billing->order;
            } else {
                $billing->order += 1;
                $stage = $billing->order - 1;
            }
            //$billing->approval_status = Config::get('constants.status.approved');
            $billing->reason = null;
            $billing->reason_to_reject_id = null;
            $billing->save();
            Utils::createBillingLog($billing->id, 'approved', null, null, $stage, auth()->user()->id);
        }

        foreach ($billingPayments as $billingPaymentId) {
            $billingPayment = $this->billingPayment->with(['billings'])->find($billingPaymentId);
            if ($billingPayment) {
                $billingPayment->status = Config::get('constants.billingStatus.approved');
                foreach ($billingPayment->billings as $value) {
                    if ($value->approval_status != Config::get('constants.billingStatus.approved')) {
                        $billingPayment->status = Config::get('constants.billingStatus.open');
                        break;
                    }
                }
                $billingPayment->save();
            }
        }

        DB::commit();
        return response()->json([
            'Sucesso' => 'Faturamentos aprovados',
        ], 200);
    }

    public function approve($id)
    {
        $billing = $this->billing->findOrFail($id);
        $stage = 0;
        if ($this->approvalFlow
            ->where('order', $billing->order)
            ->where('role_id', auth()->user()->role_id)
            ->doesntExist()
        ) {
            return response()->json([
                'error' => 'Não é permitido a esse usuário aprovar o faturamento ' . $billing->id . ', modifique o fluxo de aprovação.',
            ], 422);
        }

        $maxOrder = $this->approvalFlow->max('order');
        if ($billing->order >= $maxOrder) {
            $billing->approval_status = Config::get('constants.billingStatus.approved');
            $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
            if ($billingPayment) {
                $this->openOrApprovePaymentBilling($billingPayment, $billing);
            }
            $stage = $billing->order;
        } else {
            $billing->order += 1;
            $stage = $billing->order - 1;
        }
        //$billing->approval_status = Config::get('constants.status.approved');
        $billing->reason = null;
        $billing->reason_to_reject_id = null;
        $billing->save();
        Utils::createBillingLog($billing->id, 'approved', null, null, $stage, auth()->user()->id);
        return response()->json([
            'Sucesso' => 'Faturamento aprovado',
        ], 200);
    }

    public function approveMany($requestInfo)
    {
        DB::beginTransaction();
        if (array_key_exists('ids', $requestInfo)) {
            if (array_key_exists('reprove', $requestInfo) && $requestInfo['reprove'] == true) {
                foreach ($requestInfo['ids'] as $value) {
                    $billing = $this->billing->findOrFail($value);
                    $maxOrder = $this->approvalFlow->max('order');
                    $stage = 0;

                    if ($this->approvalFlow
                        ->where('order', $billing->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        DB::rollback();
                        return response()->json([
                            'error' => 'Não é permitido a esse usuário reprovar o faturamento ' . $billing->id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    $billing->approval_status = Config::get('constants.billingStatus.disapproved');

                    if ($billing->order > $maxOrder) {
                        $billing->approval_status = Config::get('constants.billingStatus.open');
                        $stage = $billing->order;
                    } else if ($billing->order != 0) {
                        $billing->order -= 1;
                        $stage = $billing->order - 1;
                    }

                    $billing->reason = null;
                    $billing->save();
                    Utils::createBillingLog($billing->id, 'rejected', null, null, $stage, auth()->user()->id);
                }
                DB::commit();
                return response()->json([
                    'Sucesso' => 'Faturamentos reprovados',
                ], 200);
            } else {
                $billingPayments = [];
                $maxOrder = $this->approvalFlow->max('order');
                $arrayOrder = $this->approvalFlow
                    ->where('role_id', auth()->user()->role_id)
                    ->pluck('order')->toArray();
                $billings = $this->billing->whereIn('id', $requestInfo['ids'])->get();
                foreach ($billings as $billing) {
                    $stage = 0;
                    if (!in_array($billing->order, $arrayOrder)) {
                        DB::rollback();
                        return response()->json([
                            'error' => 'Não é permitido a esse usuário aprovar a conta ' . $billing->id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if ($billing->order >= $maxOrder) {
                        $billing->approval_status = Config::get('constants.billingStatus.approved');
                        if ($billing->billing_payment_id && !in_array($billing->billing_payment_id, $billingPayments)) {
                            $billingPayments[] = $billing->billing_payment_id;
                        }
                        // $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
                        // // push $billing->billing_payment_id if !array_key_exists
                        // if($billingPayment){
                        //     $this->openOrApprovePaymentBilling($billingPayment, $billing);
                        // }
                        $stage = $billing->order;
                    } else {
                        $billing->order += 1;
                        $stage = $billing->order - 1;
                    }
                    //$billing->approval_status = Config::get('constants.status.approved');
                    $billing->reason = null;
                    $billing->reason_to_reject_id = null;
                    $billing->save();
                    Utils::createBillingLog($billing->id, 'approved', null, null, $stage, auth()->user()->id);
                }

                foreach ($billingPayments as $billingPaymentId) {
                    $billingPayment = $this->billingPayment->with(['billings'])->find($billingPaymentId);
                    if ($billingPayment) {
                        $billingPayment->status = Config::get('constants.billingStatus.approved');
                        foreach ($billingPayment->billings as $value) {
                            if ($value->approval_status != Config::get('constants.billingStatus.approved')) {
                                $billingPayment->status = Config::get('constants.billingStatus.open');
                                break;
                            }
                        }
                        $billingPayment->save();
                    }
                }

                DB::commit();
                return response()->json([
                    'Sucesso' => 'Faturamentos aprovados',
                ], 200);
            }
        } else {
            DB::rollback();
            return response()->json([
                'error' => 'Nenhum faturamento selecionado',
            ], 422);
        }
    }

    public function reprove($id, Request $request)
    {
        $billing = $this->billing->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $stage = 0;
        if ($this->approvalFlow
            ->where('order', $billing->order)
            ->where('role_id', auth()->user()->role_id)
            ->doesntExist()
        ) {
            return response()->json([
                'error' => 'Não é permitido a esse usuário reprovar a conta ' . $billing->id . ', modifique o fluxo de aprovação.',
            ], 422);
        }

        $billing->approval_status = Config::get('constants.billingStatus.disapproved');

        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
        if ($billingPayment) {
            $billingPayment->status = Config::get('constants.billingStatus.open');
            $billingPayment->save();
        }
        if ($billing->order > $maxOrder) {
            $billing->approval_status = Config::get('constants.billingStatus.open');
            $stage = $billing->order;
        } else if ($billing->order != 0) {
            $billing->order -= 1;
            $stage = $billing->order - 1;
        }

        $billing->reason = $request->reason;
        $billing->reason_to_reject_id = $request->reason_to_reject_id;
        $billing->save();
        $motive = isset($request->reason_to_reject_id) ? HotelReasonToReject::findOrFail($request->all()['reason_to_reject_id'])->title : null;
        Utils::createBillingLog($billing->id, 'rejected', $motive, null, $stage, auth()->user()->id);
        return response()->json([
            'Sucesso' => 'Faturamento reprovado',
        ], 200);
    }

    public function getBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        // $cangooroo = $this->cangoorooService->updateCangoorooData($billing['reserve'], $billing['cangooroo_booking_id'], $billing['cangooroo_service_id']);
        // $billingInfo['payment_status'] = $this->getPaymentStatus($billing, $cangooroo);
        // $billingInfo['status_123'] = $this->get123Status($cangooroo);
        // $billing->fill($billingInfo);
        // $billingSuggestion = $this->getBillingSuggestion($billing, $cangooroo, $id);
        // $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        // $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        // $billing->fill($billingInfo)->save();
        return $this->billing->with($this->with)->findOrFail($id);
    }

    public function postBilling(Request $request)
    {
        $billingInfo = $request->all();
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
        $billingInfo['status_123'] = $cangooroo['status_123'];
        $billingInfo['payment_status'] = $cangooroo['payment_status'];
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        if (array_key_exists('preview', $billingInfo) && $billingInfo['preview']) {
            return [
                // 'status_123' => $billingInfo['status_123'],
                // 'payment_status' => $billingInfo['payment_status'],
                'suggestion' => $billingInfo['suggestion'],
                'suggestion_reason' => $billingInfo['suggestion_reason'],
                'suggested_date' => $this->getSuggestedDate($cangooroo)
            ];
        }
        $billingInfo['billing_payment_id'] = $this->syncBillingPayment($billingInfo, $cangooroo);
        if (is_array($billingInfo['billing_payment_id']) && (array_key_exists('error', $billingInfo['billing_payment_id']))) {
            return response()->json([
                'error' => 'Existem divergências para esse Código de Boleto: '. self::translatedField[$billingInfo['billing_payment_id']['error']],
                'code' => 'INCONSISTENT_VALUES',
                'field' => $billingInfo['billing_payment_id']['error']
            ], 422);
        }
        if (array_key_exists('bank_account', $billingInfo)) {
            $bankAccount = new BankAccount;
            $bankAccount = $bankAccount->create($billingInfo['bank_account']);
            $billingInfo['bank_account_id'] = $bankAccount->id;
        }
        $billing = new Billing;
        $billing = $billing->create($billingInfo);
        $this->syncAttachments($billing, $billingInfo, $request);
        Utils::createBillingLog($billing->id, 'created', null, null, 0, $billingInfo['user_id']);
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function putBilling($id, Request $request)
    {
        $billingInfo = $request->all();
        $billing = $this->billing->findOrFail($id);
        $billingOld = $this->billing->with($this->with)->findOrFail($id);
        if ($billing->approval_status == Config::get('constants.billingStatus.approved')) {
            return response()->json([
                'error' => 'Pedido previamente aprovado, não é possível editar',
            ], 422);
        }
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
        $billingInfo['order'] = 1;
        $billingInfo['approval_status'] =  Config::get('constants.billingStatus.open');
        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
        if ($billingPayment) {
            if (count($billingPayment->billings) <= 1) $billingPayment->delete();
            else {
                $billingPayment->status = Config::get('constants.billingStatus.open');
                $billingPayment->save();
            }
        }
        $billingInfo['reason'] = null;
        $billingInfo['reason_to_reject_id'] = null;
        //$cangooroo = Cangooroo::where('service_id', $billingInfo['cangooroo_service_id'])->first();
        // $billingInfo['cangooroo_booking_id'] = $cangooroo['booking_id'];
        $billingInfo['status_123'] = $cangooroo['status_123'];
        $billingInfo['payment_status'] = $cangooroo['payment_status'];
        $billingSuggestion = $this->getBillingSuggestion($billingInfo, $cangooroo, $id);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        if (array_key_exists('preview', $billingInfo) && $billingInfo['preview']) {
            return [
                // 'status_123' => $billingInfo['status_123'],
                // 'payment_status' => $billingInfo['payment_status'],
                'suggestion' => $billingInfo['suggestion'],
                'suggestion_reason' => $billingInfo['suggestion_reason'],
                'suggested_date' => $this->getSuggestedDate($cangooroo),
            ];
        }
        $billingInfo['billing_payment_id'] = $this->syncBillingPayment($billingInfo, $cangooroo);
        if (is_array($billingInfo['billing_payment_id']) && (array_key_exists('error', $billingInfo['billing_payment_id']))) {
            $this->billingPayment->where('id', $billing->billing_payment_id)->update(['deleted_at' => null]);
            return response()->json([
                'error' => 'Existem divergências para esse Código de Boleto: '. self::translatedField[$billingInfo['billing_payment_id']['error']],
                'code' => 'INCONSISTENT_VALUES',
                'field' => $billingInfo['billing_payment_id']['error']
            ], 422);
        }
        if (array_key_exists('bank_account', $billingInfo)) {
            $bankAccount = BankAccount::where('id', $billing['bank_account_id'])->first();
            if ($bankAccount) $bankAccount->fill($billingInfo['bank_account'])->save();
            else {
                $bankAccount = new BankAccount;
                $bankAccount = $bankAccount->create($billingInfo['bank_account']);
                $billingInfo['bank_account_id'] = $bankAccount->id;
            }
        }
        activity()->disableLogging();
        $billing->fill($billingInfo)->save();
        activity()->enableLogging();
        $this->putAttachments($id, $billingInfo, $request);
        $billingNew = $this->billing->with($this->with)->findOrFail($id);
        Utils::createManualLog($billingOld, $billingNew, auth()->user()->id, $this->billing, 'billing');
        Utils::createBillingLog($billing->id, 'updated', null, null, $billing->order, auth()->user()->id);
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function deleteBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $billingPayment = $this->billingPayment->with(['billings'])->find($billing->billing_payment_id);
        if ($billingPayment) {
            if (count($billingPayment->billings) <= 1) $billingPayment->delete();
            else {
                $this->openOrApprovePaymentBilling($billingPayment, $billing);
            }
        }
        $billing->billing_payment_id = null;
        $billing->approval_status =  Config::get('constants.billingStatus.canceled');
        $billing->save();
        Utils::createBillingLog($billing->id, 'deleted', null, null, $billing->order, auth()->user()->id);
        return true;
    }

    public static function getPaymentStatus($cangooroo)
    {
        // $paidReserves = PaidBillingInfo::where('reserve', $billing['reserve'])->get();
        $paidReserves = PaidBillingInfo::where('service_id', $cangooroo['service_id'])->get();
        if (empty($paidReserves->toArray())) {
            return "Não Pago";
        } else {
            $sum = 0;
            foreach ($paidReserves as $paidReserve) {
                $sum += $paidReserve['supplier_value'];
            }
            if ($sum >= ($cangooroo['selling_price'] - 5)) return "Pago";
            else return "Pago - Parcial";
        }
    }

    public function getBillingSuggestion($billingInfo, $cangooroo, $billingId = null)
    {
        $suggestionReason = '';
        $cancellationValueToUse = 0;
        if ($billingInfo['payment_status'] != 'Não Pago') {
            $suggestionReason = $suggestionReason . ' | Reserva deve estar em aberto';
        }
        if ($billingInfo['status_123'] != 'Emitida' && $billingInfo['status_123'] != 'Emitido' && $billingInfo['status_123'] != 'Reservado' && $cangooroo['client_name'] != 'MaxMilhas') {
            $suggestionReason = $suggestionReason . ' | Reserva não emitida no Admin';
        }
        //if($this->billing->where('id', '!=' , $billingId)->where('reserve', $billingInfo['reserve'])->where('cangooroo_service_id', $billingInfo['cangooroo_service_id'])->whereIn('approval_status', [0,1])->first()){
        $duplicated = $this->billing->where('id', '!=', $billingId)->where('cangooroo_service_id', $billingInfo['cangooroo_service_id'])->whereIn('approval_status', [0, 1])->get();
        if (!empty($duplicated->toArray())) {
            $sum = 0;
            foreach ($duplicated as $dup) {
                $sum += $dup['supplier_value'];
            }
            if ($sum + $billingInfo['supplier_value'] > $cangooroo['selling_price']){
                $suggestionReason = $suggestionReason . ' | Reserva cadastrada em duplicidade';
            }
        }
        if ($cangooroo['status'] == 'Cancelled') {
            $cancellationDate = (!$cangooroo['cancellation_date'] || strtotime($cangooroo['cancellation_date']) <= 1) ? $cangooroo['last_update'] : $cangooroo['cancellation_date'];
            $cancellationStartDate = (strtotime($cangooroo['check_in']) > strtotime("+5 days", strtotime($cangooroo['reservation_date']))) ? strtotime("+3 days", strtotime($cangooroo['cancellation_policies_start_date'])) : strtotime($cangooroo['cancellation_policies_start_date']);
            if (strtotime($cancellationDate) > strtotime($cangooroo['check_in'])) {
                $cancellationValueToUse = $cangooroo['selling_price'];
            } else if (strtotime($cancellationDate) > $cancellationStartDate) {
                $cancellationValueToUse = $cangooroo['cancellation_policies_value'];
            }
            if ($cancellationValueToUse != $billingInfo['supplier_value']) {
                $suggestionReason = $suggestionReason . ' | Valor informado diferente do valor de Cancelamento: R$ ' . $cancellationValueToUse;
            }
        } else {
            if ($cangooroo['status'] != 'Confirmed') {
                $suggestionReason = $suggestionReason . ' | Reserva não confirmada no Cangooroo';
            }
            if ($billingInfo['form_of_payment'] == 0) {
                if ($cangooroo['selling_price'] != $billingInfo['supplier_value']) {
                    $suggestionReason = $suggestionReason . ' | Valor informado diferente do valor no Cangooroo';
                }
            } else {
                if (($cangooroo['selling_price'] - 5) >= $billingInfo['supplier_value'] || ($cangooroo['selling_price'] + 5) <= $billingInfo['supplier_value']) {
                    $suggestionReason = $suggestionReason . ' | Valor informado diferente do valor no Cangooroo';
                }
            }
        }
        if (!$cangooroo->hotel->is_valid) {
            $suggestionReason = $suggestionReason . ' | Hotel não validado';
        }
        if ($cangooroo->hotel->cpf_cnpj != $billingInfo['cnpj'] && $cangooroo->hotel->cnpj_extra != $billingInfo['cnpj']) {
            $suggestionReason = $suggestionReason . ' | Cnpj do Titular diferente dos CNPJ cadastrados para esse hotel';
        }
        if ($cangooroo['provider_name'] != 'Omnibees' && $cangooroo['provider_name'] != 'HSystem' && $cangooroo['provider_name'] != 'Trend') {
            $suggestionReason = $suggestionReason . ' | Reserva referente a Broker';
        }
        if ($cangooroo['is_vcn']) {
            $suggestionReason = $suggestionReason . ' | A forma de pagamento para essa reserva é VCN';
        }
        if ($suggestionReason == '') {
            $suggestion = true;
        } else {
            $suggestion = false;
            $suggestionReason = substr_replace($suggestionReason, '', 0, 3);
        }
        return [
            'suggestion' => $suggestion,
            'suggestion_reason' => $suggestionReason
        ];
    }

    public static function get123Status($cangooroo)
    {
        $token = self::get123Token();
        if ($token) {
            $apiCall = Http::withHeaders([
                'Shared-Id' => '123',
            ])->withToken($token)->get(env('API_123_STATUS_URL', "https://api.123milhas.com/api/v3/hotel/booking/status/") . $cangooroo['123_id']);
            if ($apiCall->status() != 200) {

                $packageCall = Http::withHeaders([
                    'Shared-Id' => '123',
                ])->withToken($token)->get(env('API_123_STATUS_URL', "https://api.123milhas.com/api/v3/hotel/package/status/") . $cangooroo['booking_id']);

                if ($packageCall->status() != 200) { // N.D dados de pacote inválidos na base 123
                    return null;
                } else {
                    $response = $packageCall->json();
                }
            } else {
                $response = $apiCall->json();
            }
            return $response['status'];
        } else {
            return null; //erro ao autenticar api 123, contate o suporte
        }
    }

    //         return null; // N.D dados de reserva inválidos na base 123
    //         }
    //         $response = $apiCall->json();
    //         return $response['status'];
    //     }
    //     else{
    //         return null; //erro ao autenticar api 123, contate o suporte
    //     }
    // }

    public static function get123Token()
    {
        $apiCall = Http::withHeaders([
            'secret' => env('API_123_SECRET', Config::get('constants.123_secret')),
        ])->get(env('API_123_AUTH_URL', "https://api.123milhas.com/api/v3/client/auth"));
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
        $billingInfo['payment_status'] = $this->getPaymentStatus($cangooroo);
        $billingInfo['status_123'] = $this->get123Status($cangooroo);
        $billing->fill($billingInfo);
        $billingSuggestion = $this->getBillingSuggestion($billing, $cangooroo, $id);
        $billingInfo['suggestion'] = $billingSuggestion['suggestion'];
        $billingInfo['suggestion_reason'] = $billingSuggestion['suggestion_reason'];
        $billing->fill($billingInfo)->save();
        $billing['cangooroo'] = $cangooroo['status'];
        return $billing;
    }

    public function syncBillingPayment($billingInfo, $cangooroo)
    {
        $changeAllPayment = isset($billingInfo['change_all_payment']) && $billingInfo['change_all_payment'] == true;
        $fields = ['pay_date', 'recipient_name', 'oracle_protocol', 'cnpj'];
        $billingInfo['hotel_id'] = $cangooroo['hotel_id'];
        if (!is_null($billingInfo['boleto_code'])) {
            $findBillingPayment = BillingPayment::where('boleto_code', $billingInfo['boleto_code'])->where('status', 0)->first();
            if ($findBillingPayment) {
                foreach ($fields as $field) {
                    if ($changeAllPayment) {
                        $findBillingPayment[$field] = $billingInfo[$field];
                        $this->billing->where('billing_payment_id', $findBillingPayment->id)->update([$field => $billingInfo[$field]]);
                    } else if ($field == 'pay_date') {
                        if (strtotime($billingInfo[$field]) != strtotime($findBillingPayment[$field])) return ['error' => $field];
                    } else if ($billingInfo[$field] != $findBillingPayment[$field]) return ['error' => $field];
                }
                $findBillingPayment->status = Config::get('constants.billingStatus.open');
                $findBillingPayment->save();
                return $findBillingPayment->id;
            } else {
                $billingPayment = new BillingPayment();
                $billingPayment = $billingPayment->create($billingInfo);
                return $billingPayment->id;
            }
        } else {
            $billingPayment = new BillingPayment();
            $billingPayment = $billingPayment->create($billingInfo);
            return $billingPayment->id;
        }
    }

    public function openOrApprovePaymentBilling($billingPayment, $billing)
    {
        $billingPayment->status = Config::get('constants.billingStatus.approved');
        foreach ($billingPayment->billings as $value) {
            if ($value->approval_status != Config::get('constants.billingStatus.approved') && $value->id != $billing->id) {
                $billingPayment->status = Config::get('constants.billingStatus.open');
                break;
            }
        }
        $billingPayment->save();
    }

    public function getBillingUsers()
    {
        $usersArray = [];
        $userIds = $this->billing->where('approval_status', '!=', Config::get('constants.billingStatus.canceled'))->distinct()->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $data = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
            array_push($usersArray, $data);
        }
        return $usersArray;
    }

    public function getBillingClients()
    {
        $clients = Cangooroo::where('client_name','!=',null)->distinct()->pluck('client_name');
        return $clients;
    }

    public function syncAttachments($billing, $billingInfo, Request $request)
    {
        if (array_key_exists('attachments', $billingInfo)) {
            foreach ($billingInfo['attachments'] as $key => $attachment) {
                $billingHasAttachments = new BillingHasAttachments;
                $attachment['attachment'] = $this->storeAttachment($request, $key);
                $billingHasAttachments = $billingHasAttachments->create([
                    'billing_id' => $billing->id,
                    'attachment' => $attachment['attachment'],
                ]);
            }
        }
    }

       public function putAttachments($id, $billingInfo, Request $request)
    {

        $updateAttachments = [];
        $createdAttachments = [];

        if (array_key_exists('attachments', $billingInfo)) {
            foreach ($billingInfo['attachments'] as $key => $attachment) {
                if (array_key_exists('id', $attachment)) {
                    $updateAttachments[] = $attachment['id'];
                } else {
                    $billingHasAttachments = new BillingHasAttachments;
                    $attachment['attachment'] = $this->storeAttachment($request, $key);
                    $billingHasAttachments = $billingHasAttachments->create([
                        'billing_id' => $id,
                        'attachment' => $attachment['attachment'],
                    ]);
                    $createdAttachments[] = $billingHasAttachments->id;
                }
            }
        }
        $this->attachments->where('billing_id', $id)->whereNotIn('id', $updateAttachments)->whereNotIn('id', $createdAttachments)->delete();
    }

    public function storeAttachment(Request $request, $key)
    {
        $data = uniqid(date('HisYmd'));

        if ($request->hasFile('attachments.' . $key . '.attachment') && $request->file('attachments.' . $key . '.attachment')->isValid()) {
            $extensionAttachment = $request['attachments.' . $key . '.attachment']->extension();
            $originalNameAttachment  = explode('.', $request['attachments.' . $key . '.attachment']->getClientOriginalName());
            $nameFileAttachment = "{$originalNameAttachment[0]}_{$data}.{$extensionAttachment}";
            $uploadAttachment = $request['attachments.' . $key . '.attachment']->storeAs('attachment', $nameFileAttachment);

            if (!$uploadAttachment) {
                return response('Falha ao realizar o upload do arquivo.', 500)->send();
            }
            return $nameFileAttachment;
        }
    }

    public function getSuggestedDate($cangooroo)
    {
        $paymentCondition = $cangooroo->hotel->payment_condition;
        $paymentConditionDays = $cangooroo->hotel->payment_condition_days;
        $paymentConditionBefore = $cangooroo->hotel->payment_condition_before;
        $paymentConditionUtile = $cangooroo->hotel->payment_condition_utile;
        if($paymentCondition == 0)
        {
            return null;
        }
        $dateUsed = null;
        switch ($paymentCondition) {
            case 1:
                $dateUsed = $cangooroo->check_in;
                break;
            case 2:
                $dateUsed = $cangooroo->check_out;
                break;
            case 3:
                $dateUsed = $cangooroo->reservation_date;
                break;
        }
        if(!$paymentConditionUtile){
            return date('Y-m-d', strtotime($dateUsed. ' '.($paymentConditionBefore ? '-':'+').' '.$paymentConditionDays.' days'));
        }
        if($paymentConditionBefore){
            return Utils::getLastUtileDay($dateUsed, $paymentConditionDays, true);
        }
        return Utils::getLastUtileDay($dateUsed, $paymentConditionDays);
    }

    const translatedField = [
        'pay_date' => 'Data de Pagamento',
        'recipient_name' => 'Nome do titular',
        'oracle_protocol' => 'Protocolo Oracle',
        'cnpj' => 'CNPJ',
        'hotel_id' => 'Id do Hotel'
    ];
}
