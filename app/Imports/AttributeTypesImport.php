<?php

namespace App\Imports;

use App\Models\AttributeType;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class AttributeTypesImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new AttributeType([
            'title'     => $row['titulo'],
            'default'     => $row['padrao'],
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255',
            'padrao' => 'boolean',
        ];
    }
}
