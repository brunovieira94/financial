<?php

namespace App\Jobs;

use App\Exports\TestExport;
use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Storage;
use Throwable;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;
    private $exportFile;

    public function __construct($request, $exportFile)
    {
        $this->request = $request;
        $this->exportFile = $exportFile;
    }

    public function handle()
    {
        $request = $this->request;
        $exportFile = $this->exportFile;
        (new TestExport($request))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        Export::where('id', $exportFile['id'])
            ->update([
                'status' => 1,
                'link' => Storage::disk('s3')->temporaryUrl($exportFile['path'], now()->addDays(2))
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Export::where('id', $this->exportFile['id'])
            ->update([
                'status' => 5,
                'error' => $exception->getMessage(),
            ]);
    }
}
