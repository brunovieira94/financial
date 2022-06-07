<?php

namespace App\Exports;

use App\Models\Hotel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HotelsExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    public function collection()
    {
        return Hotel::get();
    }

    public function map($Hotel): array
    {
        return [
            $Hotel->id_hotel_cangooroo,
            $Hotel->id_hotel_omnibees,
            $Hotel->hotel_name,
            $Hotel->chain,
            $Hotel->email,
            $Hotel->email_omnibees,
            $Hotel->phone,
            $Hotel->billing_type,
            // $Hotel->group_form_payment() ? $Hotel->group_form_payment()->title : $Hotel->group_form_payment(),
            // $Hotel->bank_account() ? $Hotel->bank_account()->title : $Hotel->bank_account(),
            // $Hotel->bank_account() ? $Hotel->bank_account()->title : $Hotel->bank_account(),
            // $Hotel->bank_account() ? $Hotel->bank_account()->agency_number->bank()->bank_code : $Hotel->bank_account(),
            // $Hotel->bank_account() ? $Hotel->bank_account()->account_type : $Hotel->bank_account(),
            $Hotel->holder_full_name,
            $Hotel->cpf_cnpj,
            $Hotel->is_valid,
            $Hotel->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'Id Cangoroo',
            'Id Omnibees',
            'Royalty',
            'Rede',
            'E-mail respondido',
            'E-mail cadastro Ominibees',
            'Telefone',
            'Tipo de faturamento',
            // 'Forma de pagamento',
            // 'Banco',
            // 'Código do Banco',
            // 'Agência',
            // 'Tipo de Conta',
            'Nome Completo do Titular',
            'CPF/CNPJ',
            'Validação CNPJ',
            'Data de Criação',
        ];
    }
}
