<?php

namespace App\Imports;

use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class PaymentMethodsImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new PaymentMethod([
            'title'     => $row['titulo'],
            'initials'    => $row['sigla'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255',
            'sigla' => 'required|max:255',
        ];
    }
}
