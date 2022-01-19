<?php

namespace App\Imports;

use App\Models\State;
use App\Models\Country;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class StatesImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $country;

    public function model(array $row)
    {
        return new State([
            'title'     => $row['titulo'],
            'country_id' => $this->country->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:150|unique:states,title,NULL,id,deleted_at,NULL',
            'pais' => ['required', function($attribute, $value, $onFailure) {
                if($this->country == null){
                    $onFailure('Pais não cadastrado');
                }
            }]
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->country = Country::where('title', $value['pais'])->first();
        }
    }
}
