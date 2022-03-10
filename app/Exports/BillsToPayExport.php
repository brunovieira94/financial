<?php

namespace App\Exports;

use App\Models\PaymentRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class BillsToPayExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    private $requestInfo;
    private $totalTax;

    public function __construct($requestInfo){
        $this->requestInfo = $requestInfo;
    }

    use Exportable;

    public function collection()
    {
        $infoRequest = $this->requestInfo;
        $query = PaymentRequest::query()->with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
        if(array_key_exists('cpfcnpj', $infoRequest)){
            $query->whereHas('provider', function ($query) use ($infoRequest){
                $query->where('cpf', $infoRequest['cpfcnpj'])->orWhere('cnpj', $infoRequest['cpfcnpj']);
            });
        }
        if(array_key_exists('provider', $infoRequest)){
            $query->whereHas('provider', function ($query) use ($infoRequest){
                $query->where('id', $infoRequest['provider']);
            });
        }
        if(array_key_exists('chart_of_accounts', $infoRequest)){
            $query->whereHas('chart_of_accounts', function ($query) use ($infoRequest){
                $query->where('id', $infoRequest['chart_of_accounts']);
            });
        }
        if(array_key_exists('cost_center', $infoRequest)){
            $query->whereHas('cost_center', function ($query) use ($infoRequest){
                $query->where('id', $infoRequest['cost_center']);
            });
        }
        if(array_key_exists('payment_request', $infoRequest)){
            $query->where('id', $infoRequest['payment_request']);
        }
        if(array_key_exists('user', $infoRequest)){
            $query->whereHas('user', function ($query) use ($infoRequest){
                $query->where('id', $infoRequest['user']);
            });
        }
        if(array_key_exists('status', $infoRequest)){
            $query->whereHas('approval', function ($query) use ($infoRequest){
                $query->where('status', $infoRequest['status']);
            });
        }
        if(array_key_exists('approvalOrder', $infoRequest)){
            $query->whereHas('approval', function ($query) use ($infoRequest){
                $query->where('order', $infoRequest['approvalOrder']);
            });
        }
        if(array_key_exists('created_at', $infoRequest)){
            if(array_key_exists('from', $infoRequest['created_at'])){
                $query->where('created_at', '>=', $infoRequest['created_at']['from']);
            }
            if(array_key_exists('to', $infoRequest['created_at'])){
                $query->where('created_at', '<=', $infoRequest['created_at']['to']);
            }
            if(!array_key_exists('to', $infoRequest['created_at']) && !array_key_exists('from', $infoRequest['created_at'])){
                $query->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if(array_key_exists('pay_date', $infoRequest)){
            if(array_key_exists('from', $infoRequest['pay_date'])){
                $query->where('pay_date', '>=', $infoRequest['pay_date']['from']);
            }
            if(array_key_exists('to', $infoRequest['pay_date'])){
                $query->where('pay_date', '<=', $infoRequest['pay_date']['to']);
            }
            if(!array_key_exists('to', $infoRequest['pay_date']) && !array_key_exists('from', $infoRequest['pay_date'])){
                $query->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if(array_key_exists('extension_date', $infoRequest)){
            if(array_key_exists('from', $infoRequest['extension_date'])){
                $query->whereHas('installments', function ($query) use ($infoRequest){
                    $query->where('extension_date', '>=', $infoRequest['extension_date']['from']);
                });
            }
            if(array_key_exists('to', $infoRequest['extension_date'])){
                $query->whereHas('installments', function ($query) use ($infoRequest){
                    $query->where('extension_date', '<=', $infoRequest['extension_date']['to']);
                });
            }
            if(!array_key_exists('to', $infoRequest['extension_date']) && !array_key_exists('from', $infoRequest['extension_date'])){
                $query->whereHas('installments', function ($query) use ($infoRequest){
                    $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                });
            }
        }
        if(array_key_exists('days_late', $infoRequest)){
            $query->whereHas('installments', function ($query) use ($infoRequest){
                $query->where('status', '!=', 'BD')->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($infoRequest['days_late']));
            });
        }
        return $query->get();
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
            $paymentRequest->provider ? ($paymentRequest->provider->company_name ? $paymentRequest->provider->company_name : $paymentRequest->provider->full_name) : $paymentRequest->provider,
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
            'CNPJ do Fornecedor',
            'Nome do Fornecedor',
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
