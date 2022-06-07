<?php

namespace App\Exports;

use App\Models\Billing;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BillingExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    public function collection()
    {
        return Billing::get();
    }

    public function map($Billing): array
    {
        return [
            $Billing->cangooroo_booking_id,
            $Billing->reserve,
            $Billing->supplier_value,
            $Billing->pay_date,
            $Billing->boleto_value,
            $Billing->boleto_code,
            $Billing->recipient_name,
            $Billing->remark,
            $Billing->oracle_protocol,
            $Billing->user ? $Billing->user->name : $Billing->user,
            $Billing->payment_status,
            $Billing->status_123,
            $Billing->cnpj,
        ];
    }

    public function headings(): array
    {
        return [
            'Id Reserva Cangooroo',
            'Reserva',
            'Valor do Fornecedor',
            'Data de Pagamento',
            'Valor do Boleto',
            'Código do Boleto',
            'Nome do Beneficiário Final',
            'Observação',
            'Protocolo Oracle',
            'Usuário',
            'Status do Pagamento',
            'Status 123',
            'Cnpj',
        ];
    }
}
