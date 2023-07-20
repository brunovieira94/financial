<?php

namespace App\Exports;

use App\Models\AccountsPayableApprovalFlowLog;
use Illuminate\Support\Collection;
use Vitorccs\LaravelCsv\Concerns\Exportable;
use Vitorccs\LaravelCsv\Concerns\FromQuery;
use Vitorccs\LaravelCsv\Concerns\WithHeadings;
use Vitorccs\LaravelCsv\Concerns\WithMapping;
use Storage;

class TestExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;
    private $requestInfo;
    protected $shouldQueue;
    private $exportFile;

    public function __construct($requestInfo, $exportFile)
    {
        $this->requestInfo = $requestInfo;
        $this->exportFile = $exportFile;
    }

    public function query()
    {
        return AccountsPayableApprovalFlowLog::query();
    }

    public function map($arraySizRequest): array
    {
        return [
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
        ];
    }

    public function headings(): array
    {
        return [
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
            'TESTE',
        ];
    }
}
