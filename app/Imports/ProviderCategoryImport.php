<?php

namespace App\Imports;

use App\Models\ProviderCategory;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProviderCategoryImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    public function model(array $row)
    {
        return new ProviderCategory([
            'title'     => $row['titulo'],
            'payment_before_weekends'    => $row['antecipar_pagamento_nos_fins_de_semana'] == 'Sim' ? true : false,
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:150|unique:provider_categories,title,NULL,id,deleted_at,NULL',
            'antecipar_pagamento_nos_fins_de_semana' => 'required|in:Sim,Não',
        ];
    }
}
