<?php

namespace App\Imports;

use App\Models\Currency;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class CurrenciesImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new Currency([
            'title'     => $row['titulo'],
            'initials'    => $row['sigla'],
            'default'     => $row['padrao'],
            'currency_symbol'    => $row['simbolo'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255|unique:currency,title,NULL,id,deleted_at,NULL',
            'sigla' => 'required|max:255',
            'padrao' => 'boolean',
            'simbolo' => 'required|max:255',
        ];
    }
}
