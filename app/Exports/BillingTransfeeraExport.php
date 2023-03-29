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

class BillingTransfeeraExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    private $requestInfo;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    public function collection()
    {
        return Billing::whereIn('billing_payment_id', $this->requestInfo['billing_payments_ids'])->get();
    }

    public function map($billing): array
    {
        /** @var Cangooroo*/
        $cangooroo = $billing->cangooroo;
        /** @var Hotel*/
        $hotel = !is_null($cangooroo) ? $cangooroo->hotel : null;
        /** @var BankAccount*/
        $bankAccount = $billing->bank_account;
        return [
            !is_null($cangooroo) ? $cangooroo->hotel_name : '',
            $billing->cnpj,
            !is_null($hotel) ? $hotel->email : '',
            !is_null($bankAccount) ? (!is_null($bankAccount->bank) ? $bankAccount->bank->bank_code : '') : '',
            !is_null($bankAccount) ? (!!($bankAccount->agency_check_number) ? $bankAccount->agency_number.'-'.$bankAccount->agency_check_number : $bankAccount->agency_number) : '',
            !is_null($bankAccount) ? $bankAccount->account_number : '',
            !is_null($bankAccount) ? $bankAccount->account_check_number : '',
            !is_null($bankAccount) && !is_null($bankAccount->account_type) ? $bankAccount->accountTypesTransfeera[$bankAccount->account_type] : '',
            $billing->supplier_value,
            $billing->reserve,
            '',
            $billing->cangooroo_service_id,
        ];
    }

    public function headings(): array
    {
        return [
            'Nome ou Razão Social',
            'CPF ou CNPJ',
            'Email (opcional)',
            'Banco',
            'Agência',
            'Conta',
            'Dígito da conta',
            'Tipo de Conta (Corrente ou Poupança)',
            'Valor',
            'ID integração (opcional)',
            'Data de agendamento (opcional)',
            'Descrição Pix (opcional)',
        ];
    }
}
