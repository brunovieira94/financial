<?php

namespace App\Exports;

use App\Models\PaymentRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllDuePaymentRequestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $result = PaymentRequest::with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
        if(array_key_exists('from', $this->requestInfo)){
            $result = $result->where('pay_date', '>=', $this->requestInfo['from']);
        }
        if(array_key_exists('to', $this->requestInfo)){
            $result = $result->where('pay_date', '<=', $this->requestInfo['to']);
        }
        if(!array_key_exists('to', $this->requestInfo) && !array_key_exists('from', $this->requestInfo)){
            $result = $result->whereBetween('pay_date', [now(), now()->addMonths(1)]);
        }
        return $result->get();
        //return PaymentRequest::with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'])->get();
    }

    public function map($paymentRequest): array
    {
        $this->totalTax = 0;
        foreach ($paymentRequest->tax as $value) {
            $this->totalTax += $value['tax_amount'];
        }

        return [
            $paymentRequest->provider ? ($paymentRequest->provider->cnpj ? $paymentRequest->provider->cnpj : $paymentRequest->provider->cpf) : $paymentRequest->provider,
            $paymentRequest->emission_date,
            $paymentRequest->pay_date,
            $paymentRequest->amount,
            $paymentRequest->chart_of_accounts ? $paymentRequest->chart_of_accounts->title : $paymentRequest->chart_of_accounts,
            $paymentRequest->cost_center ? $paymentRequest->cost_center->title : $paymentRequest->cost_center,
            $paymentRequest->business ? $paymentRequest->business->name : $paymentRequest->business,
            $paymentRequest->currency ? $paymentRequest->currency->title : $paymentRequest->currency,
            $paymentRequest->exchange_rate,
            $paymentRequest->frequency_of_installments,
            $paymentRequest->days_late,
            $paymentRequest->payment_type,
            $paymentRequest->user ? $paymentRequest->user->email : $paymentRequest->user,
            $paymentRequest->invoice_number,
            $paymentRequest->invoice_type,
            $paymentRequest->bar_code,
            $paymentRequest->net_value,
            $paymentRequest->created_at,
            $this->totalTax,
        ];
    }

    public function headings(): array
    {
        return [
            'Fornecedor',
            'Data de Emissão',
            'Data de Pagamento',
            'Valor',
            'Plano de Contas',
            'Centro de Custo',
            'Negócio',
            'Moeda',
            'Taxa de Câmbio',
            'Frequência de Parcelas',
            'Dias de atraso',
            'Tipo de pagamento',
            'Usuário',
            'Número da fatura',
            'Tipo de fatura',
            'Código de barras',
            'Valor Líquido',
            'Data de Criação',
            'Total de Impostos',
        ];
    }
}
