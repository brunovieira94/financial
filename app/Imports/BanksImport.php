<?php

namespace App\Imports;

use App\Models\Bank;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class BanksImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new Bank([
            'title'     => $row['titulo'],
            'cnab400'    => $row['cnab_400'],
            'cnab240'     => $row['cnab_240'],
            'bank_code'    => $row['codigo_do_banco'],
        ]);
    }

    public function rules(): array
    {
        return [
            'codigo_do_banco' => 'numeric',
            'titulo' => 'required|max:150',
            'cnab_400' => 'required|boolean',
            'cnab_240' => 'required|boolean',
        ];
    }
}
