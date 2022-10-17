<?php

namespace App\Exports;

use App\Models\ChartOfAccounts;
use App\Models\CostCenter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CostCentersExportEditable implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{
    use Exportable;

    private $parent;

    public function collection()
    {
        return CostCenter::whereNull('parent')->get();
    }

    public function map($costCenterExport): array
    {
        return [
            $costCenterExport->id,
            $costCenterExport->code,
            $costCenterExport->title,
            $costCenterExport->active ? 'Sim' : 'Não',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Codigo',
            'Descricao do centro de custo',
            'Ativo',
        ];
    }
}
