<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class CompaniesImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $city;

    public function model(array $row)
    {
        return new Company([
            'company_name'     => $row['razao_social'],
            'trade_name' => $row['nome_fantasia'],
            'cnpj' => $row['cnpj'],
            'cep' => $row['cep'],
            'cities_id' => $row['cidade'] == null ? null : $this->city->id,
            'address' => $row['endereco'],
            'number' => $row['numero'],
            'complement' => $row['complemento'],
            'district' => $row['distrito'],
        ]);
    }

    public function rules(): array
    {
        return [
            'razao_social' => 'required|max:45',
            'nome_fantasia' => 'max:150|nullable',
            'cnpj' => 'max:45|nullable',
            'cep' => 'max:10|nullable',
            'endereco' => 'max:250|nullable',
            'numero' => 'max:250|nullable',
            'complemento' => 'max:150|nullable',
            'distrito' => 'max:150|nullable',
            'cidade' => [function($attribute, $value, $onFailure) {
                if($value != null) {
                    if($this->city == null){
                        $onFailure('Cidade: Nao encontrada');
                    }
                }
            }],
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->city = City::where('title', $value['cidade'])->first();
        }
    }
}
