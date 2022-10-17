<?php

namespace App\Exports;

use App\Models\ChartOfAccounts;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChartOfAccountsExportEditable implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    use Exportable;

    private $parent;

    public function collection()
    {
        return ChartOfAccounts::get();
    }

    public function map($chartOfAccountsExport): array
    {
        switch ($chartOfAccountsExport->group) {
            case 1:
                $chartOfAccountsExport->group = "Ativo";
                break;
            case 2:
                $chartOfAccountsExport->group = "Passivo";
                break;
            case 3:
                $chartOfAccountsExport->group = "Resultado";
                break;
            default:
                $chartOfAccountsExport->group = "Informe o nome do grupo";
        }

        return [
            $chartOfAccountsExport->id,
            $chartOfAccountsExport->group,
            $chartOfAccountsExport->parent,
            $chartOfAccountsExport->code,
            $chartOfAccountsExport->title,
            $chartOfAccountsExport->managerial_code,
            $chartOfAccountsExport->managerial_title,
            $chartOfAccountsExport->group_code,
            $chartOfAccountsExport->group_title,
            $chartOfAccountsExport->referential_code,
            $chartOfAccountsExport->referential_title,
            $chartOfAccountsExport->active ? 'Sim' : 'Não',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Grupo',
            'Codigo do Pai',
            'Codigo Contabil',
            'Descricao do plano de contas Contabil',
            'Codigo Gerencial',
            'Descricao do plano de contas Gerencial',
            'Codigo Grupo',
            'Descricao do plano de contas Grupo',
            'Codigo Referencial',
            'Descricao do plano de contas Referencial',
            'Ativo',
        ];
    }
}
