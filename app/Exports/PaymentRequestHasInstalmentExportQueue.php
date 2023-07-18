<?php

namespace App\Exports;

use App\Exports\Utils as ExportsUtils;
use App\Models\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Storage;

class PaymentRequestHasInstalmentExportQueue implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, WithEvents, ShouldQueue
{
    private $fileName;
    private $paymentRequestInstallmentClean;
    private $exportFile;

    public function __construct($fileName, $paymentRequestInstallmentClean, $exportFile)
    {
        $this->fileName = $fileName;
        $this->paymentRequestInstallmentClean = $paymentRequestInstallmentClean;
        $this->exportFile = $exportFile;
    }

    use Exportable;

    public function collection()
    {
        return $this->paymentRequestInstallmentClean;
    }

    public function map($installment): array
    {
        return ExportsUtils::exportInstallmentData($installment);
    }

    public function headings(): array
    {
        return ExportsUtils::exportInstallmentColumn();
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
