<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use App\Services\Utils;
use Config;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrderExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    use Exportable;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $requestInfo = $this->requestInfo;
        $query = PurchaseOrder::query()->with(['user', 'installments', 'approval', 'cost_centers', 'attachments', 'services', 'products', 'company', 'currency', 'provider', 'purchase_requests']);
        $query = Utils::baseFilterPurchaseOrder($query, $requestInfo);

        return $query->get();
    }

    public function map($purchaseOrder): array
    {
        $row = [];
        $costCenters = [];
        if ($purchaseOrder->cost_centers != null) {
            foreach ($purchaseOrder->cost_centers as $center) {
                if ($center != null) {
                    $costCenters[] = [
                        'Id: ' . (!is_null($center->cost_center['id']) ? $center->cost_center['id'] : ''),
                        'Titulo: ' . (!is_null($center->cost_center['title']) ? $center->cost_center['title'] : ''),
                        'Porcentagem: ' . (!is_null($center->percentage) ? $center->percentage : '')
                    ];
                }
            }
        }

        $initTotalProducts = 0;
        $discountTotalProducts = 0;
        foreach ($purchaseOrder->products as $product) {
            $initTotalProducts += ((!is_null($product->unitary_value) ? $product->unitary_value : 0) * (!is_null($product->quantity) ? $product->quantity : 0));
            $discountTotalProducts += (!is_null($product->money_discount) ? $product->money_discount : 0);
        }

        $initTotalServices = 0;
        $discountTotalServices = 0;
        foreach ($purchaseOrder->services as $service) {
            $initTotalServices += ((!is_null($service->unitary_value) ? $service->unitary_value : 0) * (!is_null($service->quantity) ? $service->quantity : 0));
            $discountTotalServices += (!is_null($service->money_discount) ? $service->money_discount : 0);
        }

        $products = [];
        if ($purchaseOrder->products != null) {
            foreach ($purchaseOrder->products as $product) {
                if ($product != null) {
                    $products = [
                        'Id: ' . (!is_null($product->product['id']) ? $product->product['id'] : ''),
                        'Titulo: ' . (!is_null($product->product['title']) ? $product->product['title'] : ''),
                        'Quantidade: ' . (!is_null($product->quantity) ? $product->quantity : ''),
                        'Valor Unitário: ' . (!is_null($product->unitary_value) ? $product->unitary_value : ''),
                        'Desconto %: ' . (!is_null($product->percentage_discount) ? $product->percentage_discount : ''),
                        'Desconto em dinheiro: ' . (!is_null($product->money_discount) ? $product->money_discount : ''),
                        'Valor do Produto: ' . (((!is_null($product->unitary_value) ? $product->unitary_value : 0) * (!is_null($product->quantity) ? $product->quantity : 0)) - (!is_null($product->money_discount) ? $product->money_discount : 0)),
                    ];
                }

                $row[] = [
                    $purchaseOrder->id,
                    $purchaseOrder->provider ? ($purchaseOrder->provider->cnpj ? 'CNPJ: ' . $purchaseOrder->provider->cnpj : 'CPF: ' . $purchaseOrder->provider->cpf) : $purchaseOrder->provider,
                    $purchaseOrder->provider ? ($purchaseOrder->provider->company_name ? $purchaseOrder->provider->company_name : $purchaseOrder->provider->full_name) : $purchaseOrder->provider,
                    $purchaseOrder->company ? $purchaseOrder->company->company_name : $purchaseOrder->company,
                    ($purchaseOrder->order_type == 0) ? "Normal" : (($purchaseOrder->order_type == 1) ? "Urgente" : "Regularização"),
                    $purchaseOrder->billing_date,
                    $purchaseOrder->currency ? $purchaseOrder->currency->title : $purchaseOrder->currency,
                    $initTotalProducts + $initTotalServices,
                    (($initTotalProducts - $discountTotalProducts) - $purchaseOrder->money_discount_products) + (($initTotalServices - $discountTotalServices) - $purchaseOrder->money_discount_services),
                    $purchaseOrder->installments ? ($purchaseOrder->installments->sum('portion_amount') - $purchaseOrder->installments->sum('money_discount')) : '',
                    (!is_null($purchaseOrder->approval) && !is_null($purchaseOrder->approval->approval_flow)) ? $purchaseOrder->approval->approval_flow->role['title'] : '',
                    !is_null($purchaseOrder->approval) ? Config::get('constants.statusPt.' . $purchaseOrder->approval->status) : '',
                    json_encode($costCenters, JSON_UNESCAPED_UNICODE),
                    json_encode($products, JSON_UNESCAPED_UNICODE),
                    '',
                    $purchaseOrder->user ? $purchaseOrder->user->email : $purchaseOrder->user,
                    ($purchaseOrder->payment_requests != []) ? (($purchaseOrder->payment_requests[0]['status'] == 0) ? "Novo" : (($purchaseOrder->payment_requests[0]['status'] == 1) ? "Pendente" : (($purchaseOrder->payment_requests[0]['status'] == 2) ? "Concluído" : ''))) : '',
                    $purchaseOrder->created_at,
                ];
            }
        }

        $services = [];
        if ($purchaseOrder->services != null) {
            foreach ($purchaseOrder->services as $service) {
                if ($service != null) {
                    $services = [
                        'Id: ' . (!is_null($service->service['id']) ? $service->service['id'] : ''),
                        'Titulo: ' . (!is_null($service->service['title']) ? $service->service['title'] : ''),
                        'Quantidade: ' . (!is_null($service->quantity) ? $service->quantity : ''),
                        'Valor Unitário: ' . (!is_null($service->unitary_value) ? $service->unitary_value : ''),
                        'Desconto %: ' . (!is_null($service->percentage_discount) ? $service->percentage_discount : ''),
                        'Desconto em dinheiro: ' . (!is_null($service->money_discount) ? $service->money_discount : ''),
                        'Data Inicial: ' . (!is_null($service->initial_date) ? $service->initial_date : ''),
                        'Data Final: ' . (!is_null($service->end_contract_date) ? $service->end_contract_date : ''),
                        'Tempo de Duração do Contrato: ' . (!is_null($service->contract_time) ? $service->contract_time : ''),
                        'Unidade de duração de Contrato: ' . (!is_null($service->contract_frequency) ? $service->contract_frequency : ''),
                        'Valor do Serviço: ' . (((!is_null($service->unitary_value) ? $service->unitary_value : 0) * (!is_null($service->quantity) ? $service->quantity : 0)) - (!is_null($service->money_discount) ? $service->money_discount : 0)),

                    ];
                }

                $row[] = [
                    $purchaseOrder->id,
                    $purchaseOrder->provider ? ($purchaseOrder->provider->cnpj ? 'CNPJ: ' . $purchaseOrder->provider->cnpj : 'CPF: ' . $purchaseOrder->provider->cpf) : $purchaseOrder->provider,
                    $purchaseOrder->provider ? ($purchaseOrder->provider->company_name ? $purchaseOrder->provider->company_name : $purchaseOrder->provider->full_name) : $purchaseOrder->provider,
                    $purchaseOrder->company ? $purchaseOrder->company->company_name : $purchaseOrder->company,
                    ($purchaseOrder->order_type == 0) ? "Normal" : (($purchaseOrder->order_type == 1) ? "Urgente" : "Regularização"),
                    $purchaseOrder->billing_date,
                    $purchaseOrder->currency ? $purchaseOrder->currency->title : $purchaseOrder->currency,
                    $initTotalProducts + $initTotalServices,
                    (($initTotalProducts - $discountTotalProducts) - $purchaseOrder->money_discount_products) + (($initTotalServices - $discountTotalServices) - $purchaseOrder->money_discount_services),
                    $purchaseOrder->installments ? ($purchaseOrder->installments->sum('portion_amount') - $purchaseOrder->installments->sum('money_discount')) : '',
                    (!is_null($purchaseOrder->approval) && !is_null($purchaseOrder->approval->approval_flow)) ? $purchaseOrder->approval->approval_flow->role['title'] : '',
                    !is_null($purchaseOrder->approval) ? Config::get('constants.statusPt.' . $purchaseOrder->approval->status) : '',
                    json_encode($costCenters, JSON_UNESCAPED_UNICODE),
                    '',
                    json_encode($services, JSON_UNESCAPED_UNICODE),
                    $purchaseOrder->user ? $purchaseOrder->user->email : $purchaseOrder->user,
                    ($purchaseOrder->payment_requests != []) ? (($purchaseOrder->payment_requests[0]['status'] == 0) ? "Novo" : (($purchaseOrder->payment_requests[0]['status'] == 1) ? "Pendente" : (($purchaseOrder->payment_requests[0]['status'] == 2) ? "Concluído" : ''))) : '',
                    $purchaseOrder->created_at,
                ];
            }
        }

        return $row;
    }

    public function headings(): array
    {
        return [
            'Id',
            'Identificação do Fornecedor',
            'Nome do Fornecedor',
            'Empresa',
            'Tipo de Pedido',
            'Data de Pagamento',
            'Moeda',
            'Valor Total Inicial',
            'Valor Total Negociado',
            'Valor Total das Parcelas',
            'Etapa Atual',
            'Status Atual',
            'Centro de Custos',
            'Produtos',
            'Serviços',
            'Usuário',
            'Status da Entrada Detalhada',
            'Data de Criação',
        ];
    }
}
