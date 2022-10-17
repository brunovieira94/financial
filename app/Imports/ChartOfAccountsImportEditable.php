<?php

namespace App\Imports;

use App\Models\ChartOfAccounts;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class ChartOfAccountsImportEditable implements ToModel, WithValidation, WithHeadingRow
{

    private $codes = [];
    private $titles = [];

    use Importable;

    public function model(array $row)
    {
        switch ($row['grupo']) {
            case "Ativo":
                $row['grupo'] = 1;
                break;
            case "Passivo":
                $row['grupo'] = 2;
                break;
            case "Resultado":
                $row['grupo'] = 3;
                break;
        }
        switch ($row['ativo']) {
            case "Sim":
                $row['ativo'] = 1;
                break;
            case "Não":
                $row['ativo'] = 0;
                break;
        }

        if (is_numeric($row['id'])) {
            $chartOfAccounts = ChartOfAccounts::findOrFail($row['id']);
            $chartOfAccounts->fill(
                [
                    'group'     => $row['grupo'],
                    'code'    => $row['codigo_contabil'],
                    'parent'    => $row['codigo_do_pai'],
                    'title' => $row['descricao_do_plano_de_contas_contabil'],
                    'managerial_code' => $row['codigo_gerencial'],
                    'managerial_title' => $row['descricao_do_plano_de_contas_gerencial'],
                    'group_code' => $row['codigo_grupo'],
                    'group_title' => $row['descricao_do_plano_de_contas_grupo'],
                    'referential_code' => $row['codigo_referencial'],
                    'referential_title' => $row['descricao_do_plano_de_contas_referencial'],
                    'active' => $row['ativo'],
                ]
            )->save();
        } else {
            return new ChartOfAccounts([
                'group'     => $row['grupo'],
                'parent'    => $row['codigo_do_pai'],
                'code'    => $row['codigo_contabil'],
                'title' => $row['descricao_do_plano_de_contas_contabil'],
                'managerial_code' => $row['codigo_gerencial'],
                'managerial_title' => $row['descricao_do_plano_de_contas_gerencial'],
                'group_code' => $row['codigo_grupo'],
                'group_title' => $row['descricao_do_plano_de_contas_grupo'],
                'referential_code' => $row['codigo_referencial'],
                'referential_title' => $row['descricao_do_plano_de_contas_referencial'],
                'active' => $row['ativo'],
                'parent' => null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'grupo' => ['required', 'string', Rule::in(['Ativo', 'Passivo', 'Resultado'])],
            'ativo' => ['required', 'string', Rule::in(['Sim', 'Não'])],
            'descricao_do_plano_de_contas_contabil' => 'required|max:255',
            'codigo_gerencial' => 'nullable|max:255',
            'descricao_do_plano_de_contas_gerencial' => 'nullable|max:255',
            'codigo_grupo' => 'nullable|max:255',
            'descricao_do_plano_de_contas_grupo' => 'nullable|max:255',
            'codigo_referencial' => 'nullable|max:255',
            'descricao_do_plano_de_contas_referencial' => 'nullable|max:255',
            'codigo_contabil' => 'required|max:255',
        ];
    }

    public function withValidator($validator)
    {

        foreach ($validator->getData() as $key => $data) {
            if (is_numeric($data['codigo_do_pai'])) {
                if (!ChartOfAccounts::where('id', $data['codigo_do_pai'])
                    ->exists()) {
                    $error = ['Código do pai não encontrado.'];
                    $failures[] = new Failure($key, 'código do pai', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            } else {
                if (!empty($data['codigo_do_pai'])) {
                    $error = ['Código do pai inválido.'];
                    $failures[] = new Failure($key, 'código do pai', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            }
            if (is_numeric($data['id'])) {
                if (!ChartOfAccounts::where('id', $data['id'])
                    ->exists()) {
                    $error = ['ID não encontrado.'];
                    $failures[] = new Failure($key, 'código do pai', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            } else {
                if (!empty($data['id'])) {
                    $error = ['ID inválido.'];
                    $failures[] = new Failure($key, 'ID', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            }

            if (in_array($data['codigo_contabil'], $this->codes)) {
                $error = ['O código contábil está duplicado na planilha'];
                $failures[] = new Failure($key, 'Código Contábil', $error);
                throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
            } else {
                array_push($this->codes, $data['codigo_contabil']);
            }

            if (in_array($data['codigo_contabil'], $this->titles)) {
                $error = ['A descrição do plano de contas contábil está duplicada na planilha'];
                $failures[] = new Failure($key, 'descrição do plano de contas contábil cadastrado', $error);
                throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
            } else {
                array_push($this->titles, $data['descricao_do_plano_de_contas_contabil']);
            }

            if (is_numeric($data['id'])) {
                ChartOfAccounts::where('id', $data['id'])->exists();
                if (ChartOfAccounts::where('id', $data['id'])->exists()) {
                    if (ChartOfAccounts::where('code', $data['codigo_contabil'])
                        ->where('id', '!=', $data['id'])
                        ->whereNull('parent')
                        ->exists()
                    ) {
                        $error = ['Já existe este código contábil cadastrado.'];
                        $failures[] = new Failure($key, 'Código Contábil', $error);
                        throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                    }
                    if (ChartOfAccounts::where('title', $data['descricao_do_plano_de_contas_contabil'])
                        ->where('id', '!=', $data['id'])
                        ->whereNull('parent')
                        ->exists()
                    ) {
                        $error = ['Já existe a descrição do plano de contas contábil cadastrado no sistema.'];
                        $failures[] = new Failure($key, 'descrição do plano de contas contábil cadastrado', $error);
                        throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                    }
                } else {
                    $error = ['ID informado não encontrado no banco de dados'];
                    $failures[] = new Failure($key, 'ID', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            } else {
                if (ChartOfAccounts::where('code', $data['codigo_contabil'])
                    ->whereNull('parent')
                    ->exists()
                ) {
                    $error = ['Já existe este código contábil cadastrado.'];
                    $failures[] = new Failure($key, 'Código Contábil', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
                if (ChartOfAccounts::where('title', $data['descricao_do_plano_de_contas_contabil'])
                    ->whereNull('parent')
                    ->exists()
                ) {
                    $error = ['Já existe a descrição do plano de contas contábil cadastrado no sistema.'];
                    $failures[] = new Failure($key, 'descrição do plano de contas contábil cadastrado', $error);
                    throw new \Maatwebsite\Excel\Validators\ValidationException(\Illuminate\Validation\ValidationException::withMessages($error), $failures);
                }
            }
        }
    }
}
