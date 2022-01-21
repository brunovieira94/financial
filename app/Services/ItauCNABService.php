<?php

namespace App\Services;

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class ItauCNABService
{
    private $paymentRequest;
    private $installments;
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];
    private $withPaymentRequest = ['approval', 'installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(PaymentRequest $paymentRequest, Company $company, PaymentRequestHasInstallments $installments)
    {
        $this->paymentRequest = $paymentRequest;
        $this->company = $company;
        $this->installments = $installments;
    }

    public function generateCNAB240Shipping($requestInfo)
    {
        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = null;

        foreach ($company->bank_account as $bank) {
            if ($bank->id == $requestInfo['bank_account_id'])
                $bankAccount = $bank;
        }

        $recipient = new \App\Helpers\Pessoa(
            [
                'nome'      => $company->company_name,
                'endereco'  => $company->address,
                'cep'       => $company->cep,
                'uf'        => $company->city->state->uf,
                'cidade'    => $company->city->title,
                'documento' => $company->cnpj,
                'numero' => $company->number,
                'complemento' => $company->complement,
            ]
        );

        $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Itau(
            [
                'agencia'      => $bankAccount->agency_number,
                'conta'        => $bankAccount->account_number,
                'contaDv'      => $bankAccount->account_check_number ?? '',
                'carteira'     => '112',
                'beneficiario' => $recipient,
            ]
        );

        $allPaymentRequest = $this->paymentRequest->with($this->withPaymentRequest)->whereIn('id', $requestInfo['payment_request_ids'])->get();
        $billets = [];

        foreach ($allPaymentRequest as $paymentRequest) {

            $payer = new \App\Helpers\Pessoa(
                [
                    'nome'      => $paymentRequest->provider->provider_type == 'F' ? $paymentRequest->provider->full_name : $paymentRequest->provider->company_name,
                    'endereco'  => $paymentRequest->provider->address,
                    'cep'       => $paymentRequest->provider->cep,
                    'uf'        => $paymentRequest->provider->city->state->title,
                    'cidade'    => $paymentRequest->provider->city,
                    'documento' => $paymentRequest->provider->provider_type == 'F' ? $paymentRequest->provider->cpf : $paymentRequest->provider->cnpj,
                    'numero' => $paymentRequest->provider->number,
                    'complemento' => $paymentRequest->provider->complement,
                ]
            );

            foreach ($paymentRequest->installments as $installment) {

                if ($installment->codBank != 'BD') {
                    $billet = new \App\Helpers\Boleto\Banco\Itau(
                        [
                            'dataVencimento'         => new Carbon($installment->due_date),
                            'valor'                  => $installment->portion_amount,
                            'transferTypeIdentification' => $paymentRequest->bank_account_provider->account_type,
                            'numeroDocumento'        => $installment->id,
                            'pagador'                => $payer,
                            'beneficiario'           => $recipient,
                            'agencia'                => $paymentRequest->bank_account_provider->agency_number,
                            'conta'                  => $paymentRequest->bank_account_provider->account_number,
                            'contaDv'                => $paymentRequest->bank_account_provider->account_check_number,
                        ]
                    );
                }
                array_push($billets, $billet);
            }
        }

        $shipping->addBoletos($billets);
        $shipping->save(base_path() . DIRECTORY_SEPARATOR . 'cnab' . DIRECTORY_SEPARATOR . 'itau.txt');
        $file = File::get(base_path() . DIRECTORY_SEPARATOR . 'cnab' . DIRECTORY_SEPARATOR . 'itau.txt');
        Storage::disk('s3')->put('tempCNAB/itau.txt', $file);

        return response()->json([
            'linkArchive' => Storage::temporaryUrl('tempCNAB/itau.txt', now()->addMinutes(5)),
        ]);
    }

    public function receiveCNAB240($requestInfo)
    {

        $returnFile = $requestInfo->file('return-file');

        $processArchive = new \App\Helpers\Cnab\Retorno\Cnab240\Banco\Itau($returnFile);
        $processArchive->processar();

        $returnArray = $processArchive->getDetalhes();

        foreach ($returnArray as $batch) {
            $installments =  $this->installments->findOrFail($batch->numeroDocumento);
            $installments->status = $batch->ocorrencia;
            $installments->codBank = $batch->nossoNumero;
            $installments->amount_received = $batch->valorRecebido;
            $installments->save();
        }
        return response('Processo finalizado.', 200)->send();
    }
}
