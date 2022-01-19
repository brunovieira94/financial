<?php

namespace App\Imports;

use App\Models\CostCenter;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class CostCentersImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $parentId;

    public function model(array $row)
    {
        return new CostCenter([
            'title'     => $row['titulo'],
            'code'    => $row['codigo'],
            'parent' => $this->parentId->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|max:255' ,
            'codigo_do_pai' => ['nullable', function($attribute, $value, $onFailure) {
                if ($this->parentId == null) {
                    $onFailure('Pai não existe');
                }
            }],
            'codigo' => ['required', function($attribute, $value, $onFailure) {
                if($this->parentId != null){
                    if (CostCenter::where('code', $value)->where('parent', $this->parentId->id)->exists()) {
                        $onFailure('Código já cadastrado!');
                    }
                }
            }]
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            $this->parentId = CostCenter::where('code', $value['codigo_do_pai'])->first();
        }
    }
}
