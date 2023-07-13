<?php

namespace App\Jobs;

use App\Exports\TestExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        (new TestExport($request->all()))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']);
    }
}
