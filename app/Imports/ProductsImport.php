<?php

namespace App\Imports;

use App\Models\ChartOfAccounts;
use App\Models\MeasurementUnit;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithValidation, WithHeadingRow
{

    private $chartOfAccounts;
    private $measurementUnit;

    use Importable;

    public function model(array $row)
    {
        return new Product([
            'title'     => $row['titulo'],
            'measurement_units_id'    => $this->measurementUnit->id,
            'chart_of_accounts_id'    => $this->chartOfAccounts->id,
            'description'     => $row['descricao'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255',
            'unidade_de_medida' => ['required', function($attribute, $value, $onFailure) {
                if($this->measurementUnit == null){
                    $onFailure('Unidade de Medida não cadastrada');
                }
            }],
            'plano_de_contas' => ['required', function($attribute, $value, $onFailure) {
                if($this->chartOfAccounts == null){
                    $onFailure('Plano de Contas não cadastrado');
                }
            }],
            'descricao' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->chartOfAccounts = ChartOfAccounts::where('title', $value['plano_de_contas'])->first();
            $this->measurementUnit = MeasurementUnit::where('title', $value['unidade_de_medida'])->first();
            if ($this->measurementUnit == null) {
                $this->measurementUnit = MeasurementUnit::where('unit', $value['unidade_de_medida'])->first();
            }
        }
    }
}
