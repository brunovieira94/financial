<?php

namespace App\Exports;

use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\Hotel;
use App\Models\BankAccount;
use App\Models\HotelApprovalFlow;
use App\Models\HotelReasonToReject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\Utils;
use Config;

class BillingForApprovalExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    private $requestInfo;
    private $approvalStatus;

    public function __construct($requestInfo, $approvalStatus)
    {
        $this->requestInfo = $requestInfo;
        $this->approvalStatus = $approvalStatus;
    }

    public function collection()
    {
        $approvalFlowUserOrders = HotelApprovalFlow::where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrders)
            return response([], 404);

        $query = Billing::query()->with(['cangooroo.hotel.bank_account', 'approval_flow', 'user', 'reason_to_reject', 'bank_account']);
        $query = Utils::baseFilterBilling($query, $this->requestInfo);

        $query = $query->whereIn('approval_status', [0, 2])->where('deleted_at', '=', null);

        $billingIDs = [];
        foreach ($approvalFlowUserOrders as $approvalFlowOrder) {
            $billingApprovalFlow = Billing::where('order', $approvalFlowOrder['order']);
            $billingIDs = array_merge($billingIDs, $billingApprovalFlow->pluck('id')->toArray());
        }
        $query = $query->whereIn('id', $billingIDs);
        return $query->get();
    }

    public function map($billing): array
    {
        /** @var Cangooroo*/
        $cangooroo = $billing->cangooroo;
        /** @var Hotel*/
        $hotel = !is_null($cangooroo) ? $cangooroo->hotel : null;
        /** @var BankAccount*/
        $bankAccount = $billing->bank_account;
        /** @var HotelReasonToReject*/
        $reasonToReject = $billing->reason_to_reject;
        return [
            !is_null($billing->user) ? $billing->user->name : '',
            $billing->reserve,
            $billing->cangooroo_service_id,
            $billing->payment_status,
            !is_null($cangooroo) ? $cangooroo->status : '',
            $billing->status_123,
            Config::get('constants.statusPt.' . $billing->approval_status),
            !is_null($billing->approval_flow) && !is_null($billing->approval_flow->role) ? $billing->approval_flow->role->title : '',
            $billing->billing_payment_id,
            $billing->supplier_value,
            $billing->boleto_value,
            $billing->pay_date,
            $billing->boleto_code,
            $billing->remark,
            $billing->oracle_protocol,
            !is_null($cangooroo) ? $cangooroo['123_id'] : '',
            !is_null($cangooroo) ? $cangooroo->supplier_name : '',
            !is_null($cangooroo) ? $cangooroo->reservation_date : '',
            !is_null($cangooroo) ? $cangooroo->check_in : '',
            !is_null($cangooroo) ? $cangooroo->check_out : '',
            !is_null($cangooroo) ? $cangooroo->hotel_id : '',
            !is_null($cangooroo) ? $cangooroo->supplier_hotel_id : '',
            !is_null($cangooroo) ? $cangooroo->hotel_name : '',
            (!is_null($hotel) && !is_null($hotel->billing_type)) ? $hotel->billingTypes[$hotel->billing_type] : '',
            !is_null($billing->form_of_payment) ? $billing->formsOfPayment[$billing->form_of_payment] : '',
            !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->title : '') : '',
            !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->bank_code : '') : '',
            !is_null($bankAccount) ? (!!($bankAccount->agency_check_number) ? $bankAccount->agency_number.'-'.$bankAccount->agency_check_number : $bankAccount->agency_number) : '',
            !is_null($bankAccount) && !is_null($bankAccount->account_type) ? $bankAccount->accountTypes[$bankAccount->account_type] : '',
            !is_null($bankAccount) ? (!!($bankAccount->account_check_number) ? $bankAccount->account_number.'-'.$bankAccount->account_check_number : $bankAccount->account_number) : '',
            $billing->recipient_name,
            $billing->cnpj,
            !is_null($hotel) ? ($hotel->is_valid ? 'Sim' : 'Não') : '',
            !is_null($cangooroo) ? $cangooroo->selling_price : '',
            $billing->pax_in_house ? 'Sim' : 'Não',
            $billing->created_at,
            !is_null($reasonToReject) ? $reasonToReject->title : '',
            $billing->suggestion,
            $billing->suggestion_reason,
            '',
            '',
        ];
    }

    public function headings(): array
    {
        return [
            'Operador',
            'Reserva',
            'Id do Serviço',
            'Status do Pagamento',
            'Status do Cangooroo',
            'Status 123',
            'Status de Aprovação',
            'Etapa de Aprovação',
            'Id do Pagamento',
            'Valor do Parceiro',
            'Valor do Boleto',
            'Data de pagamento',
            'Código do Boleto',
            'Observação',
            'Protocolo Oracle',
            'ID 123',
            'Parceiro',
            'Data da Reserva',
            'Data Check-in',
            'Data do Check-out',
            'ID Hotel - Cangooroo',
            'ID Hotel - Parceiro',
            'Nome do Hotel',
            'Tipo de Faturamento',
            'Forma de pagamento',
            'Banco',
            'Código do Banco',
            'Agência',
            'Tipo de Conta',
            'Conta',
            'Nome Completo do Titular',
            'CNPJ',
            'CNPJ Válido?',
            'Valor Cangooroo',
            'Pax In House',
            'Data de Criação',
            'Motivo de Rejeição',
            'Sugestão',
            'Motivo',
            'Pago',
            'Obs Pagamento'
        ];
    }
}
