<?php

namespace App\Imports;

use App\Models\State;
use App\Models\City;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class CitiesImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $state;

    public function model(array $row)
    {
        return new City([
            'title'     => $row['titulo'],
            'states_id' => $this->state->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:150',
            'estado' => ['required', function($attribute, $value, $onFailure) {
                if($this->state == null){
                    $onFailure('Estado não cadastrado');
                }
            }]
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->state = State::where('title', $value['estado'])->first();
        }
    }
}
