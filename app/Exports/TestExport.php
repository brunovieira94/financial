<?php

namespace App\Exports;

use App\Models\Export;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Storage;

class TestExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, WithEvents
{
    use Exportable;
    private $requestInfo;
    public $timeout = 3600;
    protected $shouldQueue;
    private $exportFile;

    public function __construct($requestInfo, $shouldQueue = false, $exportFile)
    {
        $this->requestInfo = $requestInfo;
        $this->exportFile = $exportFile;
        $this->shouldQueue = $shouldQueue;
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

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                Export::where('id', $this->exportFile['id'])
                    ->update([
                        'status' => 1,
                        'link' => Storage::disk('s3')->temporaryUrl($this->exportFile['path'], now()->addDays(2))
                    ]);
            },
            BeforeWriting::class => function (BeforeWriting $event) {
                // Executar ações adicionais antes de escrever no arquivo
            },
        ];
    }


}
