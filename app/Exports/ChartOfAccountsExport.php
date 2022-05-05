<?php

namespace App\Exports;

use App\Models\ChartOfAccounts;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChartOfAccountsExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    private $parent;

    public function collection()
    {
        return ChartOfAccounts::get();
    }

    public function map($chartOfAccountsExport): array
    {
        $this->parent = ChartOfAccounts::where('id', $chartOfAccountsExport->parent)->first();
        return [
            $chartOfAccountsExport->group,
            $chartOfAccountsExport->code,
            $chartOfAccountsExport->title,
            $this->parent ? $this->parent->code : $chartOfAccountsExport->parent,
            $chartOfAccountsExport->managerial_code,
            $chartOfAccountsExport->managerial_title,
            $chartOfAccountsExport->group_code,
            $chartOfAccountsExport->group_title,
            $chartOfAccountsExport->referential_code,
            $chartOfAccountsExport->referential_title,
        ];
    }

    public function headings(): array
    {
        return [
            'Grupo',
            'Código Contábil',
            'Descrição do plano de contas Contábil',
            'Código do pai',
            'Código Gerencial',
            'Descrição do plano de contas Gerencial',
            'Código Grupo',
            'Descrição do plano de contas Grupo',
            'Código Referencial',
            'Descrição do plano de contas Referencial',
        ];
    }
}
