<?php

namespace App\Imports;

use App\Models\Country;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class CountriesImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new Country([
            'title'     => $row['titulo'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255|unique:countries,title,NULL,id,deleted_at,NULL',
        ];
    }
}
