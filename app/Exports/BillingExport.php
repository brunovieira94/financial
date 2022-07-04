<?php

namespace App\Exports;

use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\Hotel;
use App\Models\BankAccount;
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
        return Billing::with(['cangooroo.hotel.bank_account', 'user'])->where('approval_status', array_search($this->approvalStatus, Utils::$approvalStatus))->get();
    }

    public function map($billing): array
    {
        /** @var Cangooroo*/
        $cangooroo = $billing->cangooroo;
        /** @var Hotel*/
        $hotel = $cangooroo->hotel;
        /** @var BankAccount*/
        $bankAccount = $hotel->bank_account->first();
        return [
            $billing->user->name,
            $billing->reserve,
            $billing->payment_status,
            $cangooroo->status,
            $billing->status_123,
            $billing->supplier_value,
            $billing->boleto_value,
            $billing->pay_date,
            $billing->boleto_code,
            $billing->remark,
            $billing->oracle_protocol,
            $cangooroo['123_id'],
            $cangooroo->supplier_name,
            $cangooroo->reservation_date,
            $cangooroo->check_in,
            $cangooroo->check_out,
            $cangooroo->hotel_id,
            $cangooroo->supplier_hotel_id,
            $cangooroo->hotel_name,
            !is_null($hotel->billing_type) ? $hotel->billingTypes[$hotel->billing_type] : '',
            !is_null($bankAccount) ? $bankAccount->bank->title : '',
            !is_null($bankAccount) ? $bankAccount->bank->bank_code : '',
            !is_null($bankAccount) ? $bankAccount->agency_number : '',
            !is_null($bankAccount) && !is_null($bankAccount->account_type) ? $bankAccount->accountTypes[$bankAccount->account_type] : '',
            !is_null($hotel->form_of_payment) ? $hotel->formsOfPayment[$hotel->form_of_payment] : '',
            $hotel->holder_full_name,
            $hotel->cpf_cnpj,
            $hotel->is_valid ? 'Sim' : 'Não',
            $cangooroo->selling_price,
            $billing->created_at,
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
            'Banco',
            'Código do Banco',
            'Agência',
            'Tipo de Conta',
            'Forma de pagamento',
            'Nome Completo do Titular',
            'CPF/CNPJ',
            'CNPJ Válido?',
            'Valor Cangooroo',
            'Data de Criação',
        ];
    }
}
