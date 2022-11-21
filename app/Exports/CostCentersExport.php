<?php

namespace App\Exports;

use App\Models\CostCenter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CostCentersExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings
{

    use Exportable;

    private $parent;

    public function collection()
    {
        return CostCenter::get();
    }

    public function map($costCenter): array
    {
        $nameManager = '';
        $nameManagerConcat = false;
        $nameVicePresident = '';
        $nameVicePresidentConcat = false;
        foreach ($costCenter->vice_presidents as $vp){
            if($nameVicePresidentConcat){
                $nameVicePresident = $nameVicePresident . ' ,' . $vp->name;
            }else {
                $nameVicePresident = $vp->name;
                $nameVicePresidentConcat = true;
            }
        }
        foreach ($costCenter->managers as $m){
            if($nameManagerConcat){
                $nameManager = $nameVicePresident . ' ,' . $m->name;
            }else {
                $nameManager = $m->name;
                $nameManagerConcat = true;
            }
        }


        $this->parent = CostCenter::where('id', $costCenter->parent)->first();
        return [
            $costCenter->title,
            $costCenter->code,
            $this->parent ? $this->parent->code : $costCenter->parent,
            $nameVicePresident,
            $nameManager
        ];
    }

    public function headings(): array
    {
        return [
            'Título',
            'Código',
            'Código do pai',
            'Vice Presidente',
            'Gestor',
        ];
    }
}
