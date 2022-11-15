<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InfoQuotationExport implements FromArray, WithMultipleSheets
{
    private $sheets;

    public function __construct($sheets)
    {
        $this->sheets = $sheets;
    }

    use Exportable;

    public function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [
            new InfoQuotationProductsExport($this->sheets['products'] ?? []),
            new InfoQuotationServicesExport($this->sheets['services'] ?? []),
        ];

        return $sheets;
    }
}
