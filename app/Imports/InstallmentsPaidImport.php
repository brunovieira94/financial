<?php

namespace App\Imports;

use Config;
use Throwable;

use Carbon\Carbon;

use App\Helpers\Util;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\BankAccount;
use App\Models\Bank;
use App\Services\NotificationService;
use Exception;
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

    private $user;
    private $fileOriginalName;
    private $otherPaymentsService;
    private $rowCounter;
    private $fileStoredName;

    public static $failures = [];
    public static $errors = [];

    public function __construct($otherPaymentsService, $user, $fileOriginalName, $fileStoredName)
    {
        $this->otherPaymentsService = $otherPaymentsService;
        $this->fileOriginalName = $fileOriginalName;
        $this->fileStoredName = $fileStoredName;
        self::$failures[$fileStoredName] = [];
        self::$errors[$fileStoredName] = [];
        $this->rowCounter = 2;
        $this->user = $user;
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

            $installment = PaymentRequestHasInstallmentsClean::where('payment_request_id', $row['conta'])->where('parcel_number', $row['parcela']);
            $installment = $installment->get()->first();
            $groupFormPaymentId = Util::getGroupFormPaymentIdByName($row['forma_de_pagamento']);
            $bankAccountCompany = $this->getCompanyBankAccount($row);
            $paymentDate =  preg_replace('/[^0-9 *-\_]/', '', $row['data_do_pagamento']);
            $paymentDate = explode('/', $paymentDate);
            if (count($paymentDate) > 2) {
                $paymentDate = $paymentDate[2] . '-' . $paymentDate[1] . '-' . $paymentDate[0];
            } else {
                $paymentDate = null;
            }

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
                if (Redis::exists($this->fileStoredName)) {
                    $data = [
                        'row' => $row['row'],
                        'paymentRequest' =>  $row['conta'],
                        'installment' => $row['parcela'],
                        'error' => is_null($installment)
                            ? 'A parcela não foi encontrada'
                            : (is_null($groupFormPaymentId)
                                ? 'A Forma de pagamento não foi reconhecida'
                                : (is_null($paymentDate)
                                    ? 'A Data de Pagamento não está no formato esperado "dd/mm/YYYY"'
                                    : 'A Conta bancária não foi encontrada'))
                    ];
                    try {
                        $redisData = Redis::get($this->fileStoredName);
                        $redisData = json_decode($redisData, true);
                        $redisData[] = $data;
                        Redis::set($this->fileStoredName, json_encode($redisData));
                    } catch (Exception $e) {
                        Redis::del('h', $this->fileStoredName);
                    }
                } else {
                    Redis::set($this->fileStoredName, json_encode([
                        [
                            'row' => $row['row'],
                            'paymentRequest' =>  $row['conta'],
                            'installment' => $row['parcela'],
                            'error' => is_null($installment)
                                ? 'A parcela não foi encontrada'
                                : (is_null($groupFormPaymentId)
                                    ? 'A Forma de pagamento não foi reconhecida'
                                    : (is_null($paymentDate)
                                        ? 'A Data de Pagamento não está no formato esperado "dd/mm/YYYY"'
                                        : 'A Conta bancária não foi encontrada'))
                        ]
                    ]));
                }
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

        if ($bankAccountCompany->exists()) {
            return $bankAccountCompany->get()->first();
        } else {
            return null;
        }
    }

    public function rules(): array
    {
        return [
            //'forma_de_pagamento' => 'required|in:boleto,pix,ted,débito em conta,debito em conta,chave pix',
            // 'data_do_pagamento' => 'required|date_format:d/m/Y',
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
            self::$failures[$this->fileStoredName][] = [
                'row' => $this->rowCounter,
                'paymentRequest' =>  $row['conta'],
                'installment' => $row['parcela'],
                'error' => trim($row['conta']) === ''
                    ? 'Conta não fornecida'
                    : 'Parcela não fornecida',
            ];
        }

        $this->rowCounter += 1;

        return !$conta || !$parcela;
    }

    public function prepareForValidation($data, $index)
    { // NOTE: This is called before validation
        $data['row'] = $index;

        if (is_string($data['forma_de_pagamento'])) {
            $data['forma_de_pagamento'] = trim(mb_strtolower($data['forma_de_pagamento']));
        }

        if (is_string($data['data_do_pagamento'])) {
            $data['data_do_pagamento'] = trim($data['data_do_pagamento']);
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

            self::$failures[$this->fileStoredName][] = [
                'row' => $failure->row(),
                'error' => $failure->attribute(),
                'installment' => $failure->values()['parcela'],
                'paymentRequest' => $failure->values()['conta'],
            ];
        }
    }

    public function onError(Throwable $e)
    {
        self::$errors[$this->fileStoredName][] = $e->getTraceAsString();
    }

    public static function afterImport(AfterImport $event)
    {
        $import = $event->getConcernable();
        $failures = [];
        $errors = [];

        if (array_key_exists($import->fileStoredName, self::$failures)) {
            $failures = self::$failures[$import->fileStoredName];
        }

        if (array_key_exists($import->fileStoredName, self::$errors)) {
            $errors = self::$errors[$import->fileStoredName];
        }

        NotificationService::generateDataSendImportInstallmentsPaidReport(
            [$import->user->email],
            'Relatório da Importação de Parcelas Pagas',
            'installments-paid-import-report',
            $import->fileOriginalName,
            Redis::exists($import->fileStoredName) == true ? json_decode(Redis::get($import->fileStoredName)) : [],
            $errors
        );
        Redis::del('h', $import->fileStoredName);

        //nota: se uma job externa vir a ser usada, remova as linhas abaixo

        if (array_key_exists($import->fileStoredName, self::$failures)) {
            unset(self::$failures[$import->fileStoredName]);
        }

        if (array_key_exists($import->fileStoredName, self::$errors)) {
            unset(self::$errors[$import->fileStoredName]);
        }
    }
}
