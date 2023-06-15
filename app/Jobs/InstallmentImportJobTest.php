<?php

namespace App\Jobs;

use App\Imports\InstallmentsPaidImport;
use App\Services\NotificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InstallmentImportJobTest implements ShouldQueue
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

        try {
            $installment->import('imports/' . $this->fileStoredName, 's3');
            $failures = array_merge($this->formatFailures($installment->failures()), $installment->not_imported);
        } catch (Exception $e) {
            $errors[] = $e->getTraceAsString();
        }

        NotificationService::generateDataSendImportInstallmentsPaidReport(
            [$this->user->email],
            'Relatório da Importação de Parcelas Pagas',
            'installments-paid-import-report',
            $this->fileOriginalName,
            $failures,
            $errors
        );
    }

    public function formatFailures($failures)
    {
        $returnable = [];

        foreach ($failures as $failure) {
            $failure = (object) $failure;

            $returnable[] = [
                'row' => $failure->row(),
                'error' => $failure->attribute(),
                'installment' => $failure->values()['parcela'],
                'paymentRequest' => $failure->values()['conta'],
            ];
        }

        return $returnable;
    }
}
