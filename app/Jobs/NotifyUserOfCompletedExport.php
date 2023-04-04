<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class NotifyUserOfCompletedExport implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $filePath;
    private $export;

    public function __construct($filePath, Export $export)
    {
        $this->filePath = $filePath;
        $this->export = $export;
    }

    public function handle()
    {
        $this->export->status = true;
        $this->export->link = Storage::disk('s3')->temporaryUrl($this->filePath, now()->addDays(2));
        $this->export->save();
    }
}
