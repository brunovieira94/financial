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
        $this->parent = CostCenter::where('id', $costCenter->parent)->first();
        return [
            $costCenter->title,
            $costCenter->code,
            $this->parent ? $this->parent->code : $costCenter->parent,
        ];
    }

    public function headings(): array
    {
        return [
            'Título',
            'Código',
            'Código do pai',
        ];
    }
}
