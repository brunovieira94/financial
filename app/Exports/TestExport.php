<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

class TestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, ShouldQueue
{
    use Exportable;
    private $requestInfo;
    private $timeout = 3600;

    public function __construct($requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }

    public function collection()
    {
        $arraySizRequest = [];

        for ($i = 1; $i <= ($this->requestInfo['size_array_test'] ?? 1); $i++) {
            $arraySizRequest[] = 'TESTE EXPORT';
        }

        $collection = new Collection($arraySizRequest);

        return $collection;
    }

    public function map($arraySizRequest): array
    {
        return [
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
            $arraySizRequest,
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
