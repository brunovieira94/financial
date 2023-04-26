<?php

namespace App\Exports;

use App\Models\Hotel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class HotelsExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{

    use Exportable;

    public function collection()
    {
        return Hotel::with(['bank_account'])->get();
    }

    public function map($hotel): array
    {
        $bankAccount = $hotel->bank_account->first();
        return [
            $hotel->id_hotel_cangooroo,
            $hotel->id_hotel_omnibees,
            $hotel->hotel_name,
            $hotel->chain,
            $hotel->email,
            $hotel->email_omnibees,
            $hotel->phone,
            !is_null($hotel->billing_type) ? $hotel->billingTypes[$hotel->billing_type] : '',
            !is_null($hotel->form_of_payment) ? $hotel->formsOfPayment[$hotel->form_of_payment] : '',
            !is_null($bankAccount) ? $bankAccount->bank->title : '',
            !is_null($bankAccount) ? $bankAccount->bank->bank_code : '',
            !is_null($bankAccount) ? (!!($bankAccount->agency_check_number) || $bankAccount->agency_check_number === '0' ? $bankAccount->agency_number.'-'.$bankAccount->agency_check_number : $bankAccount->agency_number) : '',
            !is_null($bankAccount) && !is_null($bankAccount->account_type) ? $bankAccount->accountTypes[$bankAccount->account_type] : '',
            !is_null($bankAccount) ? (!!($bankAccount->account_check_number) || $bankAccount->account_check_number === '0' ? $bankAccount->account_number.'-'.$bankAccount->account_check_number : $bankAccount->account_number) : '',
            $hotel->holder_full_name,
            $hotel->cpf_cnpj,
            $hotel->is_valid ? 'Sim' : 'Não',
            $hotel->created_at,
            $hotel->payment_condition_days,
            !is_null($hotel->payment_condition) ? $hotel->paymentConditions[$hotel->payment_condition] : '',
            !is_null($hotel->payment_condition_before) ? $hotel->paymentConditionsBefore[$hotel->payment_condition_before] : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Id Cangoroo',
            'Id Omnibees',
            'Royalty',
            'Rede',
            'E-mail Respondido',
            'E-mail Cdastro Ominibees',
            'Telefone',
            'Tipo de Faturamento',
            'Forma de Pagamento',
            'Banco',
            'Código do Banco',
            'Agência',
            'Tipo de Conta',
            'Conta',
            'Nome Completo do Titular',
            'CPF/CNPJ',
            'CNPJ Válido?',
            'Data de Criação',
            'Dias (Condição de pagamento)',
            'Condição de pagamento',
            'Antes/Após'
        ];
    }
}
