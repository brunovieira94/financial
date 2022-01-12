<?php

namespace App\Imports;

use App\Models\Business;
use App\Models\Company;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class BusinessImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $company;

    public function model(array $row)
    {
        return new Business([
            'name'     => $row['nome'],
            'company_id'    => $this->company->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|max:255',
            'empresa' => ['required', function($attribute, $value, $onFailure) {
                if($this->company == null){
                    $onFailure('Empresa não cadastrada');
                }
            }]
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->company = Company::where('cnpj', $value['empresa'])->first();
            if ($this->company == null) {
                $this->company = Company::where('company_name', $value['empresa'])->first();
            }
        }
    }
}
