<?php

namespace App\Exports;

use App\Models\PaidBillingInfo;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Config;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;

class PaidBillingInfoExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{

    use Exportable;
    use Queueable;

    public $timeout = 20000;
    public $maxExceptions = 3;

    private $collection;
    private $fileName;
    private $perPage;
    private $offset;

    public function __construct($collection, $perPage, $offset, $fileName)
    {
        $this->collection = $collection;
        $this->fileName = $fileName;
        $this->perPage = $perPage;
        $this->offset = $offset;
        $this->queue = 'long-running';
    }

    public function collection()
    {
        return $this->collection->limit($this->perPage)->offset($this->offset)->get();
    }

    public function map($paidBillingInfo): array
    {
        return [
            $paidBillingInfo->reserve,
            $paidBillingInfo->operator,
            $paidBillingInfo->supplier_value,
            $paidBillingInfo->pay_date,
            $paidBillingInfo->boleto_value,
            $paidBillingInfo->boleto_code,
            $paidBillingInfo->remark,
            $paidBillingInfo->oracle_protocol,
            $paidBillingInfo->bank,
            $paidBillingInfo->bank_code,
            $paidBillingInfo->agency,
            $paidBillingInfo->account,
            $paidBillingInfo->form_of_payment,
            $paidBillingInfo->hotel_name,
            $paidBillingInfo->cnpj_hotel,
            $paidBillingInfo->payment_voucher,
            $paidBillingInfo->payment_method,
            $paidBillingInfo->payment_bank,
            $paidBillingInfo->payment_remark,
            $paidBillingInfo->service_id,
        ];
    }

    public function headings(): array
    {
        return [
            'Reserva',
            'Operador',
            'Valor Parceiro',
            'Data de pagamento',
            'Valor do Boleto',
            'Código do Boleto',
            'Observação',
            'Protocolo Oracle',
            'Banco',
            'Código',
            'Agência',
            'Conta',
            'Forma de Pagamento',
            'Nome do Hotel',
            'CNPJ / CPF',
            'Comprovante Transfeera',
            'Método de pagamento',
            'Banco de pagamento',
            'Observação de pagamento',
            'ID Serviço Cangooroo',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
