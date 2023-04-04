<?php

namespace App\Exports;

use App\Models\Billing;
use App\Models\Cangooroo;
use App\Models\Hotel;
use App\Models\BankAccount;
use App\Models\BillingPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class BillingPaymentExport implements FromQuery, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{

    use Exportable;

    private $requestInfo;
    private $fileName;

    public function __construct($requestInfo, $fileName)
    {
        $this->requestInfo = $requestInfo;
        $this->fileName = $fileName;
    }

    public function query()
    {
        $infoRequest = $this->requestInfo;
        $billingPayment = BillingPayment::query();
        if (array_key_exists('id_hotel_cangooroo', $infoRequest)) {
            $billingPayment->whereHas('cangooroo', function ($query) use ($infoRequest) {
                $query->where('hotel_id', $infoRequest['id_hotel_cangooroo']);
            });
        }
        if (array_key_exists('created_at', $infoRequest)) {
            if (array_key_exists('from', $infoRequest['created_at'])) {
                $billingPayment->where('created_at', '>=', $infoRequest['created_at']['from']);
            }
            if (array_key_exists('to', $infoRequest['created_at'])) {
                $billingPayment->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($infoRequest['created_at']['to']))));
            }
            if (!array_key_exists('to', $infoRequest['created_at']) && !array_key_exists('from', $infoRequest['created_at'])) {
                $billingPayment->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $infoRequest)) {
            if (array_key_exists('from', $infoRequest['pay_date'])) {
                $billingPayment->where('pay_date', '>=', $infoRequest['pay_date']['from']);
            }
            if (array_key_exists('to', $infoRequest['pay_date'])) {
                $billingPayment->where('pay_date', '<=', $infoRequest['pay_date']['to']);
            }
            if (!array_key_exists('to', $infoRequest['pay_date']) && !array_key_exists('from', $infoRequest['pay_date'])) {
                $billingPayment->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('status', $infoRequest)) {
            $billingPayment->where('status', $infoRequest['status']);
        }
        if (array_key_exists('form_of_payment', $infoRequest)) {
            $billingPayment->where('form_of_payment', $infoRequest['form_of_payment']);
        }
        return $billingPayment;
    }

    public function map($billingPayment): array
    {
        return [
            $billingPayment->pay_date,
            $billingPayment->boleto_value,
            $billingPayment->boleto_code,
            $billingPayment->recipient_name,
            $billingPayment->oracle_protocol,
            $billingPayment->status,
            $billingPayment->cnpj,
            !is_null($billingPayment->form_of_payment) ? $billingPayment->formsOfPayment[$billingPayment->form_of_payment] : '',
            $billingPayment->status_cnab_code,
            $billingPayment->text_cnab,
            $billingPayment->invoiced_value,
        ];
    }

    public function headings(): array
    {
        return [
            'Data de Pagamento',
            'Valor do Boleto',
            'Código do Boleto',
            'Nome do Titular',
            'Protocolo Oracle',
            'Status',
            'CNPJ',
            'Forma de pagamento',
            'Código de Status Cnab',
            'Texto Cnab',
            'Valor Total',
        ];
    }
}
