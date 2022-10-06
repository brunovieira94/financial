<?php

namespace App\Exports;

use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\Hotel;
use App\Models\BankAccount;
use App\Models\HotelReasonToReject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\Utils;

class BillingExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
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
        if ($this->approvalStatus == 'billing-all') {
            $query = Billing::query()->with(['cangooroo.hotel.bank_account', 'user', 'reason_to_reject', 'bank_account']);
        } else {
            $query = Billing::query()->with(['cangooroo.hotel.bank_account', 'user', 'reason_to_reject', 'bank_account'])->where('approval_status', array_search($this->approvalStatus, Utils::$approvalStatus));
        }
        $query = Utils::baseFilterBilling($query, $this->requestInfo);
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
            $billing->payment_status,
            !is_null($cangooroo) ? $cangooroo->status : '',
            $billing->status_123,
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
            !is_null($hotel) ? $hotel->holder_full_name : '',
            !is_null($hotel) ? $hotel->cpf_cnpj : '',
            !is_null($hotel) ? ($hotel->is_valid ? 'Sim' : 'Não') : '',
            !is_null($cangooroo) ? $cangooroo->selling_price : '',
            $billing->pax_in_house ? 'Sim' : 'Não',
            $billing->created_at,
            !is_null($reasonToReject) ? $reasonToReject->title : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Operador',
            'Reserva',
            'Status do Pagamento',
            'Status do Cangooroo',
            'Status 123',
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
            'CPF/CNPJ',
            'CNPJ Válido?',
            'Valor Cangooroo',
            'Pax In House',
            'Data de Criação',
            'Motivo de Rejeição',
        ];
    }
}
