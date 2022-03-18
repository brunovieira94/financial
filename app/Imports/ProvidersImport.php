<?php

namespace App\Imports;

use App\Models\Provider;
use App\Models\ChartOfAccounts;
use App\Models\CostCenter;
use App\Models\City;
use App\Models\User;
use App\Models\ProviderCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Imports\UtilsImport;
use App\Services\Utils;

class ProvidersImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $city;
    private $costCenter;
    private $chartOfAccounts;
    private $providerCategory;
    private $chartOfAccountsID;
    private $costCenterID;


    public function __construct(ChartOfAccounts $chartOfAccounts, CostCenter $costCenter)
    {
        $this->chartOfAccounts = $chartOfAccounts;
        $this->costCenter = $costCenter;
    }

    public function model(array $row)
    {
        return new Provider([
            'company_name' => array_key_exists('razao_social', $row) ? $row['razao_social'] : null,
            'trade_name' => array_key_exists('nome_fantasia', $row) ? $row['nome_fantasia'] : null,
            'alias' => array_key_exists('nome_fornecedor_na_123', $row) ? $row['nome_fornecedor_na_123'] : null,
            'cnpj' => array_key_exists('cnpj', $row) ? $row['cnpj'] : null,
            'user_id' => $this->user->id,
            'responsible' => $row['responsavel_fornecedor'],
            'responsible_email' => $row['e_mail_responsavel_fornecedor'],
            'responsible_phone' => $row['telefone_responsavel_fornecedor'],
            'state_subscription' => $row['assinatura_do_estado'],
            'city_subscription' => $row['inscricao_estadual'],
            'chart_of_accounts_id' => $this->chartOfAccountsID,
            'cost_center_id' => $this->costCenterID,
            'provider_categories_id' => $this->providerCategory->id,
            'cep' => $row['cep'],
            'cities_id' => $row['cidade'] == null ? null : $this->city->id,
            'address' => $row['endereco'],
            'email' => $row['email'],
            'number' => $row['numero'],
            'complement' => $row['complemento'],
            'district' => $row['distrito'],
            'phones' => explode(',', $row['telefones']),
            'provider_type' => array_key_exists('cnpj', $row) ? 'J' : 'F',
            'cpf' => array_key_exists('cpf', $row) ? $row['cpf'] : null,
            'full_name' => array_key_exists('nome_completo_do_fornecedor', $row) ? $row['nome_completo_do_fornecedor'] : null,
            'rg' => array_key_exists('rg', $row) ? $row['rg'] : null,
            'birth_date' => array_key_exists('data_de_nascimento', $row) ? Utils::formatDate($row['data_de_nascimento']) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'plano_de_contas' => [function ($attribute, $value, $onFailure) {
                if ($value != null) {
                    $this->chartOfAccountsID = UtilsImport::getLastedID($value, $this->chartOfAccounts);
                    if ($this->chartOfAccountsID == null) {
                        $onFailure('Plano de contas: Código informado invalido');
                    }
                }
            }],
            'centro_de_custo' => [function ($attribute, $value, $onFailure) {
                if ($value != null) {
                    $this->costCenterID = UtilsImport::getLastedID($value, $this->costCenter);
                    if ($this->costCenterID == null) {
                        $onFailure('Centro de custo: Código informado invalido');
                    }
                }
            }],
            'usuario_123_e_mail' => ['required', function ($attribute, $value, $onFailure) {
                if ($this->user == null) {
                    $onFailure('Usuario: usuario invalido');
                }
            }],
            'categoria_do_fornecedor' => ['required', function ($attribute, $value, $onFailure) {
                if ($this->providerCategory == null) {
                    $onFailure('Categorida do Fornecedor: Categoria selecionada invalida');
                }
            }],
            'nome_fantasia'  => 'max:150',
            'nome_fornecedor_na_123' => 'max:150',
            'responsavel_fornecedor' => 'max:250',
            'cep' => 'max:10',
            'endereco' => 'max:250',
            'numero' => 'max:250',
            'complemento' => 'max:150',
            'distrito' => 'max:150',
            'email' => 'email|max:250',
            'telefone_responsavel_fornecedor' => 'max:250',
            'e_mail_responsavel_fornecedor' => 'email|max:250',
            'rg' => 'string',
            'email' => 'max:250',
            'data_de_nascimento' => 'date_format:d/m/Y',
            'cpf' => 'numeric|unique:providers,cpf,NULL,id,deleted_at,NULL',
            'cnpj' => 'numeric|unique:providers,cnpj,NULL,id,deleted_at,NULL',
            'cidade' => [function ($attribute, $value, $onFailure) {
                if ($value != null) {
                    if ($this->city == null) {
                        $onFailure('Cidade: Nao encontrada');
                    }
                }
            }],
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->user = User::where('email', $value['usuario_123_e_mail'])->first();
            $this->providerCategory = ProviderCategory::where('title', $value['categoria_do_fornecedor'])->first();
            $this->city = City::where('title', $value['cidade'])->first();
        }
    }
}
