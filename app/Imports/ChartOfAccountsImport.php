<?php

namespace App\Imports;

use App\Models\ChartOfAccounts;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class ChartOfAccountsImport implements ToModel, WithValidation, WithHeadingRow
{

    use Importable;

    private $parentId;
    private $verify = false;
    private $group = null;

    public function model(array $row)
    {
        return new ChartOfAccounts([
            'title'     => $row['descricao_plano_de_contas_contabil'],//descricao_plano_de_contas_contabil
            'code'    => $row['codigo_do_plano_de_contas_contabil'],//codigo_do_plano_de_contas_contabil
            'parent' => $this->parentId->id ?? null,
            'group' => $this->group,
            'managerial_title' => $row['descricao_plano_de_contas_gerencial'],
            'managerial_code' => $row['codigo_do_plano_de_contas_gerencial'],
            'group_title' => $row['codigo_do_plano_de_contas_grupo'],
            'group_code' => $row['descricao_plano_de_contas_grupo'],
            'referential_title' => $row['codigo_do_plano_de_contas_referencial_sped'],
            'referential_code' => $row['descricao_plano_de_contas_referencial_sped'],
        ]);
    }

    public function rules(): array
    {
        return [
            'descricao_plano_de_contas_contabil' => 'required|max:255',
            'codigo_do_plano_de_contas_contabil' => ['nullable', function($attribute, $value, $onFailure) {
                if($this->verify){
                    if ($this->parentId == null) {
                        $onFailure('Pai não existe');
                    }
                }
            }],
            'codigo_do_pai' => ['nullable', function($attribute, $value, $onFailure) {
                if($this->parentId != null){
                    if (ChartOfAccounts::where('code', $value)->where('parent', $this->parentId->id)->exists()) {
                            $onFailure('Código já cadastrado!');
                    }
                }
            }],
            'descricao_plano_de_contas_gerencial' => 'nullable|max:255',
            'descricao_plano_de_contas_grupo' => 'nullable|max:255',
            'descricao_plano_de_contas_referencial_sped' => 'nullable|max:255',
        ];
    }

    public function withValidator($validator)
    {
        foreach ($validator->getData() as $key => $value) {
            switch ($value['grupo']) {
                case "Ativo":
                    $this->group = 1;
                    break;
                case "Passivo":
                    $this->group = 2;
                    break;
                case "Resultado":
                    $this->group = 3;
                    break;
            }
            if($value['codigo_do_pai'] != null){
                $this->verify = true;
                $this->parentId = ChartOfAccounts::where('code', $value['codigo_do_pai'])->first();
            }
        }
    }
}
