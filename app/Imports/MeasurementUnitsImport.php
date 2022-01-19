<?php

namespace App\Imports;

use App\Models\MeasurementUnit;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class MeasurementUnitsImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new MeasurementUnit([
            'title'     => $row['titulo'],
            'unit'    => $row['unidade'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255',
            'unidade' => 'required|max:5',
        ];
    }
}
