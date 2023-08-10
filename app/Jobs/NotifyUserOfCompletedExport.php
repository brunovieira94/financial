<?php

namespace App\Jobs;

use App\Exports\CsvToXlsxExport;
use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;

class NotifyUserOfCompletedExport implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $filePath;
    private $export;
    private $convertXlsxFile;

    public function __construct($filePath, Export $export, $convertXlsxFile = false)
    {
        $this->filePath = $filePath;
        $this->export = $export;
        $this->convertXlsxFile = $convertXlsxFile;
    }

    public function handle()
    {
        if ($this->convertXlsxFile) {
            $s3CsvPath = $this->filePath;
            $csvContent = Storage::disk('s3')->get($s3CsvPath);
            $csvLines = explode("\n", $csvContent);
            $header = str_getcsv(array_shift($csvLines));
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray([$header], null, 'A1');
            foreach ($csvLines as $line) {
                $rowData = str_getcsv($line);
                $sheet->fromArray([$rowData], null, 'A' . ($sheet->getHighestRow() + 1));
            }
            $xlsxFileName = pathinfo($s3CsvPath, PATHINFO_FILENAME) . '.xlsx';
            $s3XlsxPath = 'exports/' . $xlsxFileName;
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            ob_start();
            $writer->save('php://output');
            $xlsxContent = ob_get_clean();
            Storage::disk('s3')->put($s3XlsxPath, $xlsxContent);
        }

        $this->export->status = true;
        $pathConverted = $this->convertXlsxFile ? 'exports/' . pathinfo($this->filePath, PATHINFO_FILENAME) . '.xlsx' : $this->filePath;
        $this->export->path = $pathConverted;
        $this->export->link = Storage::disk('s3')->temporaryUrl($this->convertXlsxFile ? $pathConverted : $this->filePath, now()->addDays(2));
        $this->export->save();
    }
}
