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

class PaidBillingInfoExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{

    use Exportable;
    use Queueable;

    public $timeout = 20000;
    public $maxExceptions = 3;

    private $requestInfo;
    private $fileName;

    public function __construct($requestInfo, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
        $this->queue = 'long-running';
    }

    public function collection()
    {
        $infoRequest = $this->requestInfo;
        $paidBillingInfo = PaidBillingInfo::query();
        if (array_key_exists('created_at', $infoRequest)) {
            if (array_key_exists('from', $infoRequest['created_at'])) {
                $paidBillingInfo->where('created_at', '>=', $infoRequest['created_at']['from']);
            }
            if (array_key_exists('to', $infoRequest['created_at'])) {
                $paidBillingInfo->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($infoRequest['created_at']['to']))));
            }
            if (!array_key_exists('to', $infoRequest['created_at']) && !array_key_exists('from', $infoRequest['created_at'])) {
                $paidBillingInfo->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $infoRequest)) {
            if (array_key_exists('from', $infoRequest['pay_date'])) {
                $paidBillingInfo->where('pay_date', '>=', $infoRequest['pay_date']['from']);
            }
            if (array_key_exists('to', $infoRequest['pay_date'])) {
                $paidBillingInfo->where('pay_date', '<=', $infoRequest['pay_date']['to']);
            }
            if (!array_key_exists('to', $infoRequest['pay_date']) && !array_key_exists('from', $infoRequest['pay_date'])) {
                $paidBillingInfo->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('form_of_payment', $infoRequest)) {
            $paidBillingInfo->where('form_of_payment', $infoRequest['form_of_payment']);
        }
        if (array_key_exists('cnpj', $infoRequest)) {
            $paidBillingInfo->where('cnpj_hotel', $infoRequest['cnpj']);
        }
        if (array_key_exists('service_id', $infoRequest)) {
            $paidBillingInfo->where('service_id', $infoRequest['service_id']);
        }
        if (array_key_exists('reserve', $infoRequest)) {
            $paidBillingInfo->where('reserve', $infoRequest['reserve']);
        }
        return $paidBillingInfo->get();
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
        return 10000;
    }
}
