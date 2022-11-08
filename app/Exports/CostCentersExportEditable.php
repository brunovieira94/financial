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
        $nameManager = '';
        $nameManagerConcat = false;
        $nameVicePresident = '';
        $nameVicePresidentConcat = false;
        foreach ($costCenterExport->vice_presidents as $vp){
            if($nameVicePresidentConcat){
                $nameVicePresident = $nameVicePresident . ' ,' . $vp->name;
            }else {
                $nameVicePresident = $vp->name;
                $nameVicePresidentConcat = true;
            }
        }
        foreach ($costCenterExport->managers as $m){
            if($nameManagerConcat){
                $nameManager = $nameVicePresident . ' ,' . $m->name;
            }else {
                $nameManager = $m->name;
                $nameManagerConcat = true;
            }
        }

        return [
            $costCenterExport->id,
            $costCenterExport->code,
            $costCenterExport->title,
            $costCenterExport->active ? 'Sim' : 'Não',
            $nameVicePresident,
            $nameManager,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Codigo',
            'Descricao do centro de custo',
            'Ativo',
            'Vice Presidente',
            'Gestor',
        ];
    }
}
