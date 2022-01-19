<?php

namespace App\Imports;

use App\Models\ChartOfAccounts;
use App\Models\Service;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class ServicesImport implements ToModel, WithValidation, WithHeadingRow
{

    private $chartOfAccounts;

    use Importable;

    public function model(array $row)
    {
        return new Service([
            'title'     => $row['titulo'],
            'chart_of_accounts_id'    => $this->chartOfAccounts->id,
            'description'     => $row['descricao'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255',
            'plano_de_contas' => ['required', function($attribute, $value, $onFailure) {
                if($this->chartOfAccounts == null){
                    $onFailure('Plano de contas não cadastrado');
                }
            }],
            'descricao' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->chartOfAccounts = ChartOfAccounts::where('title', $value['plano_de_contas'])->first();
        }
    }
}
