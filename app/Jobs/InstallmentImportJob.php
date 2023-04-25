<?php

namespace App\Jobs;

use Exception;

use App\Imports\InstallmentsPaidImport;
use App\Services\NotificationService;
use File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use finfo;
use Illuminate\Http\UploadedFile;

class InstallmentImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $otherPaymentsService;
    private $user;
    private $fileStoredName;
    private $fileOriginalName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($otherPaymentsService, $user, $fileStoredName, $fileOriginalName)
    {
        $this->fileStoredName = $fileStoredName;
        $this->fileOriginalName = $fileOriginalName;
        $this->otherPaymentsService = $otherPaymentsService;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $installment = new InstallmentsPaidImport(
            $this->otherPaymentsService,
            $this->user,
            $this->fileStoredName,
            $this->fileOriginalName
        );

        $errors = [];
        $failures = [];

        $file_path = storage_path('app' . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . $this->fileStoredName);
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $file =  new UploadedFile(
            $file_path,
            $this->fileStoredName,
            $finfo->file($file_path),
            filesize($file_path),
            0,
            false
        );

        try {
            $installment->import($file);

            if (array_key_exists($this->fileStoredName, InstallmentsPaidImport::$failures)) {
                $failures = InstallmentsPaidImport::$failures[$this->fileStoredName];
            }

            if (array_key_exists($this->fileStoredName, InstallmentsPaidImport::$errors)) {
                $errors = InstallmentsPaidImport::$errors[$this->fileStoredName];
            }
        } catch (Exception $e) {
            $errors[] = $e->getTraceAsString();
        }

        File::delete($file_path);

        NotificationService::generateDataSendImportInstallmentsPaidReport(
            [$this->user->email],
            'Relatório da Importação de Parcelas Pagas',
            'installments-paid-import-report',
            $this->fileOriginalName,
            $failures,
            $errors
        );
    }
}
