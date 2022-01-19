<?php

namespace App\Imports;

use App\Models\PaymentRequest;
use App\Models\Business;
use App\Models\Provider;
use App\Models\ChartOfAccounts;
use App\Models\CostCenter;
use App\Models\Currency;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Services\Utils;

class PaymentRequestsImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $provider;
    private $business;
    private $currency;
    private $chartOfAccounts;
    private $costCenter;

    public function __construct(ChartOfAccounts $chartOfAccounts, CostCenter $costCenter)
    {
        $this->chartOfAccounts = $chartOfAccounts;
        $this->costCenter = $costCenter;
    }

    public function model(array $row)
    {
        return new PaymentRequest([
            'provider_id'     => $this->provider->id,
            'emission_date' => Utils::formatDate($row['data_de_emissao']),
            'pay_date' => Utils::formatDate($row['data_de_pagamento']),
            'amount' => $row['valor'],
            'chart_of_accounts_id' => $this->chartOfAccountsID,
            'cost_center_id' => $this->costCenterID,
            'business_id' => $this->business->id,
            'currency_id' => $this->currency->id,
            'exchange_rate' => $row['taxa_de_cambio'],
            'frequency_of_installments' =>  $row['frequencia_de_parcelas'],
        ]);
    }

    public function rules(): array
    {
        return [
            'fornecedor' => ['required', 'numeric', function($attribute, $value, $onFailure) {
                if ($this->provider == null) {
                    $onFailure('Fornecedor: Nao foi encontrado');
                }
            }],

            'plano_de_contas' => ['required', function($attribute, $value, $onFailure) {
                $this->chartOfAccountsID = UtilsImport::getLastedID($value, $this->chartOfAccounts);
                if ($this->chartOfAccountsID == null){
                    $onFailure('Plano de contas: Código informado invalido');
                }
            }],

            'centro_de_custo' => ['required', function($attribute, $value, $onFailure) {
                $this->costCenterID = UtilsImport::getLastedID($value, $this->costCenter);
                if ($this->costCenterID == null){
                    $onFailure('Centro de custo: Código informado invalido');
                }
            }],

            'data_de_emissao' => 'required|date_format:d/m/Y',
            'data_de_pagamento' => 'required|date_format:d/m/Y',

            'negocio' => ['required',function($attribute, $value, $onFailure) {
                if ($this->business == null) {
                    $onFailure('Negocio: Nao foi encontrado');
                }
            }],

            'moeda' => ['required',function($attribute, $value, $onFailure) {
                if ($this->currency == null) {
                    $onFailure('Moeda: Nao foi encontrada');
                }
            }],
            'valor' => 'required|numeric',
            'taxa_de_cambio' => 'numeric',
            'frequencia_de_parcelas' => 'numeric',
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            if (strlen($value['fornecedor'] == 11)) {
                $this->provider = Provider::where('cpf', $value['fornecedor'])->first();
            } else  {
                $this->provider = Provider::where('cnpj', $value['fornecedor'])->first();
            }
            $this->business = Business::where('name', $value['negocio'])->first();
            $this->currency = Currency::where('title', $value['moeda'])->first();
        }
    }
}
