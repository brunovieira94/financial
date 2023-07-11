<?php

namespace App\Imports;

use Config;
use Throwable;

use App\Helpers\Util;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\BankAccount;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;

class InstallmentsPaidImport implements
    ToCollection,
    WithValidation,
    WithHeadingRow,
    SkipsEmptyRows,
    SkipsOnFailure,
    SkipsOnError,
    ShouldQueue,
    WithChunkReading,
    WithBatchInserts,
    WithEvents
{
    use Importable, RegistersEventListeners;

    // 30 minutes expiration time for redis
    static $DEFAULT_EXPIRATION_TIME = 30 * 60;

    private $user;
    private $fileOriginalName;
    private $otherPaymentsService;
    private $rowCounter;
    private $fileStoredName;
    private $importId;
    private $importFailuresId;
    private $importErrorsId;

    public function __construct($otherPaymentsService, $user, $fileOriginalName, $fileStoredName)
    {
        $this->user = $user;
        $this->otherPaymentsService = $otherPaymentsService;
        $this->fileOriginalName = $fileOriginalName;
        $this->fileStoredName = $fileStoredName;

        $this->importFailuresId = 'import:' . $fileStoredName . ':failures';
        $this->importErrorsId = 'import:' . $fileStoredName . ':errors';

        $this->rowCounter = 2;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        $paymentRequests = [];

        foreach ($rows as $row) {
            $installment = PaymentRequestHasInstallmentsClean::where('payment_request_id', $row['conta'])
                ->where('parcel_number', $row['parcela'])->get()->first();

            $groupFormPaymentId = Util::getGroupFormPaymentIdByName($row['forma_de_pagamento']);

            $bankAccountCompany = $this->getCompanyBankAccount($row);
            $paymentDate = $row['data_do_pagamento'];

            if (isset($installment) && isset($groupFormPaymentId) && isset($bankAccountCompany) && isset($paymentDate)) {
                if (!in_array($row['conta'], $paymentRequests)) {
                    $paymentRequests[] = $row['conta'];
                }

                $systemPaymentMethod = $installment->status == Config::get('constants.status.paid out')
                    ? Config::get('constants.systemPaymentMethod.update by import') // Installment already paid! Some data will be probably updated.
                    : Config::get('constants.systemPaymentMethod.import'); // Installment not paid already. Just a normal import.

                $installmentInfo = [
                    'installment_id' => $installment->id,
                    'group_form_payment_id' => $groupFormPaymentId,
                    'payment_date' => $paymentDate,
                    'bank_account_company_id' => $bankAccountCompany->id,
                    'paid_value' => $row['valor_pago'],
                    'system_payment_method' => $systemPaymentMethod,
                ];


                $this->otherPaymentsService->storeImported($this->user->id, $installmentInfo, $this->fileStoredName);
            } else {
                $failure = [
                    'row' => $row['row'],
                    'paymentRequest' =>  $row['conta'],
                    'installment' => $row['parcela'],
                    'error' => is_null($installment)
                        ? 'A parcela não foi encontrada'
                        : (is_null($groupFormPaymentId)
                            ? 'A Forma de pagamento não foi reconhecida'
                            : (is_null($paymentDate)
                                ? 'A Data de Pagamento não está no formato esperado "dd/mm/YYYY" ou "dd/mm/YY"'
                                : 'A Conta bancária não foi encontrada'))
                ];

                Redis::lpush($this->importFailuresId, json_encode($failure));
                Redis::expire($this->importFailuresId, self::$DEFAULT_EXPIRATION_TIME);
            }
        }

        if (!empty($paymentRequests)) {
            $this->otherPaymentsService->resolvePaymentRequestsStates(['payment_requests_ids' => $paymentRequests]);
        }
    }

    private function getCompanyBankAccount($row)
    {
        $accountInfo = explode('-', $row['conta_bancaria']);
        $agencyInfo = explode('-', $row['agencia']);

        $bankAccountCompany = BankAccount::with('bank')->whereLike('agency_number', "%{$agencyInfo[0]}%")->whereLike('account_number', "%{$accountInfo[0]}%");
        $bankAccountCompany = $bankAccountCompany->whereHas('bank', fn ($q) => $q->whereLike('bank_code', $row['codigo_do_banco']));

        if (count($agencyInfo) == 2)
            $bankAccountCompany = $bankAccountCompany->whereLike('agency_check_number', "%{$agencyInfo[1]}%");

        if (count($accountInfo) == 2)
            $bankAccountCompany = $bankAccountCompany->whereLike('account_check_number', "%{$accountInfo[1]}%");

        return $bankAccountCompany->exists() ? $bankAccountCompany->get()->first() : null;
    }

    public function rules(): array
    {
        return [
            //'forma_de_pagamento' => 'required|in:boleto,pix,ted,débito em conta,debito em conta,chave pix',
            //'data_do_pagamento' => 'required|date_format:Y-m-d',
            'conta' => 'required|exists:payment_requests,id',
            'parcela' => 'required|integer',
            'codigo_do_banco' => 'required:exits:banks,bank_code',
            'agencia' => 'required',
            'conta_bancaria' => 'required',
            'valor_pago' => 'required|numeric'
        ];
    }

    public function customValidationAttributes(): array
    { // These are message sent if a row don't passes validation
        return [
            'forma_de_pagamento' => 'Forma de Pagamento não reconhecida',
            'data_do_pagamento' => 'Data de pagamento inválida',
            'conta' => 'Conta (solicitação de pagamento) não encontrada no sistema',
            'parcela' => 'Número de parcela inválido',
            'codigo_do_banco' => 'Código do banco não encontrado no sistema',
            'agencia' => 'O campo Agência não foi fornecido',
            'conta_bancaria' => 'O campo Conta Bancária não foi fornecido',
            'valor_pago' => 'Valor Pago deve ser um número valido'
        ];
    }

    public function isEmptyWhen(array $row): bool
    { // When the return value is true, the respective row will be skipped from processing
        $this->rowCounter += 1;

        $formaDePagamento = trim($row['forma_de_pagamento']) !== '' ? 1 : 0;
        $dataDePagamento = trim($row['data_do_pagamento']) !== '' ? 1 : 0;
        $codigoDoBanco = trim($row['codigo_do_banco']) !== '' ? 1 : 0;
        $agencia = trim($row['agencia']) !== '' ? 1 : 0;
        $contaBancaria = trim($row['conta_bancaria']) !== '' ? 1 : 0;
        $valorPago = trim($row['valor_pago']) !== '' ? 1 : 0;
        $conta = trim($row['conta']) !== '' ? 1 : 0;
        $parcela = trim($row['parcela']) !== '' ? 1 : 0;

        $sum = $formaDePagamento + $dataDePagamento + $codigoDoBanco +
            $agencia + $contaBancaria + $valorPago + $conta +
            $parcela;

        if ($sum > 0 && (!$conta || !$parcela)) {
            $failure = json_encode([
                'row' => $this->rowCounter,
                'paymentRequest' =>  $row['conta'],
                'installment' => $row['parcela'],
                'error' => trim($row['conta']) === ''
                    ? 'Conta não fornecida'
                    : 'Parcela não fornecida',
            ]);

            Redis::lpush($this->importFailuresId, $failure);
            Redis::expire($this->importFailuresId, self::$DEFAULT_EXPIRATION_TIME);
        }

        return !$conta || !$parcela;
    }

    public function prepareForValidation($data, $index)
    { // NOTE: This is called before validation
        $data['row'] = $index;

        if (is_string($data['forma_de_pagamento'])) {
            $data['forma_de_pagamento'] = trim(mb_strtolower($data['forma_de_pagamento']));
        }

        if (is_string($data['data_do_pagamento'])) {
            $paymentDate = trim($data['data_do_pagamento']);

            $paymentDate = preg_replace('/[^0-9 *-\_]/', '', $paymentDate);
            $paymentDate = explode('/', $paymentDate);

            if (count($paymentDate) > 2) {
                $day = $paymentDate[0];
                $month = $paymentDate[1];
                $year = $paymentDate[2];

                $year = strlen($year) == 2 ? '20' . $year : $year;

                $paymentDate = $year . '-' . $month . '-' . $day;
            } else {
                $paymentDate = null;
            }
            $data['data_do_pagamento'] = $paymentDate;

        } else if (trim($data['data_do_pagamento']) != '' && $data['data_do_pagamento'] != null) {
            $paymentDate = intval($data['data_do_pagamento']);
            $paymentDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['data_do_pagamento'])->format('Y-m-d');
            $data['data_do_pagamento'] = $paymentDate;
        }

        return $data;
    }

    public static function formatFailures($failures)
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

    public static function beforeImport(BeforeImport $event)
    {
        $import = $event->getConcernable();
        //
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $failure = (object) $failure;

            $encodedFailure = json_encode([
                'row' => $failure->row(),
                'error' => $failure->attribute(),
                'installment' => $failure->values()['parcela'],
                'paymentRequest' => $failure->values()['conta'],
            ]);

            Redis::lpush($this->importFailuresId, $encodedFailure);
            Redis::expire($this->importFailuresId, self::$DEFAULT_EXPIRATION_TIME);
        }
    }

    public function onError(Throwable $e)
    {
        Redis::lpush($this->importErrorsId, json_encode([$e->getTraceAsString()]));
        Redis::expire($this->importErrorsId, self::$DEFAULT_EXPIRATION_TIME);
    }

    public static function afterImport(AfterImport $event)
    {
        $import = $event->getConcernable();

        $failures = [];
        $errors = [];

        if (Redis::exists($import->importFailuresId)) {
            while (($failure = Redis::rpop($import->importFailuresId)) != null) {
                array_push($failures, json_decode($failure));
            }
        }

        if (Redis::exists($import->importErrorsId)) {
            while (($error = Redis::rpop($import->importErrorsId)) != null) {
                array_push($errors, json_decode($error));
            }
        }

        NotificationService::generateDataSendImportInstallmentsPaidReport(
            [$import->user->email],
            'Relatório da Importação de Parcelas Pagas',
            'installments-paid-import-report',
            $import->fileOriginalName,
            Redis::exists($import->fileStoredName) == true ? json_decode(Redis::get($import->fileStoredName)) : [],
            $errors
        );
    }
}
