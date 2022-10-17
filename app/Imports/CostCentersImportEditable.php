<?php

namespace App\Imports;

use App\Models\CostCenter;
use App\Models\GroupApprovalFlow;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class CostCentersImportEditable implements ToModel, WithValidation, WithHeadingRow
{

    private $codes = [];
    private $titles = [];

    use Importable;

    public function model(array $row)
    {
        switch ($row['ativo']) {
            case "Sim":
                $row['ativo'] = 1;
                break;
            case "Não":
                $row['ativo'] = 0;
                break;
        }

        if (is_numeric($row['id'])) {
            $costCenter = CostCenter::findOrFail($row['id']);
            $costCenter->fill(
                [
                    'code'    => $row['codigo'],
                    'title' => $row['descricao_do_centro_de_custo'],
                    'active' => $row['ativo'],
                ]
            )->save();
        } else {
            $groupApprovalFlow = GroupApprovalFlow::where('default', true)->first();
            return new CostCenter([
                'code'    => $row['codigo'],
                'title' => $row['descricao_do_centro_de_custo'],
                'active' => $row['ativo'],
                'parent' => null,
                'group_approval_flow_id' => $groupApprovalFlow->id
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'ativo' => ['required', 'string', Rule::in(['Sim', 'Não'])],
            'descricao_do_centro_de_custo' => 'required|max:255',
            'codigo' => 'nullable|max:255',
        ];
    }

    public function withValidator($validator)
    {

        foreach ($validator->getData() as $key => $data) {
            if (is_numeric($data['id'])) {
                if (!CostCenter::where('id', $data['id'])
                    ->exists()) {
                    $error = ['ID não encontrado.'];
                    $failures[] = new Failure($key, 'ID', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            } else {
                if (!empty($data['id'])) {
                    $error = ['ID inválido.'];
                    $failures[] = new Failure($key, 'ID', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            }

            if (in_array($data['codigo'], $this->codes)) {
                $error = ['O código está duplicado na planilha'];
                $failures[] = new Failure($key, 'Código', $error);
                throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
            } else {
                array_push($this->codes, $data['codigo']);
            }

            if (in_array($data['descricao_do_centro_de_custo'], $this->titles)) {
                $error = ['A descrição do centro e custo está duplicada na planilha'];
                $failures[] = new Failure($key, 'descrição do centro e custo cadastrado', $error);
                throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
            } else {
                array_push($this->titles, $data['descricao_do_centro_de_custo']);
            }

            if (is_numeric($data['id'])) {
                CostCenter::where('id', $data['id'])->exists();
                if (CostCenter::where('id', $data['id'])->exists()) {
                    if (CostCenter::where('code', $data['codigo'])
                        ->where('id', '!=', $data['id'])
                        ->whereNull('parent')
                        ->exists()
                    ) {
                        $error = ['Já existe este código cadastrado.'];
                        $failures[] = new Failure($key, 'Código Contábil', $error);
                        throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                    }
                    if (CostCenter::where('title', $data['descricao_do_centro_de_custo'])
                        ->where('id', '!=', $data['id'])
                        ->whereNull('parent')
                        ->exists()
                    ) {
                        $error = ['Já existe a descrição do centro de custo cadastrado no sistema.'];
                        $failures[] = new Failure($key, 'descrição do centro de custo cadastrado', $error);
                        throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                    }
                } else {
                    $error = ['ID informado não encontrado no banco de dados'];
                    $failures[] = new Failure($key, 'ID', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            } else {
                if (CostCenter::where('code', $data['codigo'])
                    ->whereNull('parent')
                    ->exists()
                ) {
                    $error = ['Já existe este código cadastrado.'];
                    $failures[] = new Failure($key, 'Código', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
                if (CostCenter::where('title', $data['descricao_do_centro_de_custo'])
                    ->whereNull('parent')
                    ->exists()
                ) {
                    $error = ['Já existe a descrição do centro de custo cadastrado no sistema.'];
                    $failures[] = new Failure($key, 'descrição do plano de contas contábil cadastrado', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            }
        }
    }
}
