<?php

namespace App\Imports;

use App\Models\Hotel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class HotelsImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $hotel;

    public function model(array $row)
    {
        return new Hotel([]);
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator)
    {
    }
}
