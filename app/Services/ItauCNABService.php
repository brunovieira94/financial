<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\BankAccount;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Config;


class ItauCNABService
{
    private $paymentRequest;
    private $installments;
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];
    private $withPaymentRequest = ['approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(PaymentRequest $paymentRequest, Company $company, PaymentRequestHasInstallments $installments)
    {
        $this->paymentRequest = $paymentRequest;
        $this->company = $company;
        $this->installments = $installments;
    }

    public function generateCNAB240Shipping($requestInfo)
    {
        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = BankAccount::with('bank')->findOrFail($requestInfo['bank_account_id']);

        $recipient = new \App\Helpers\Pessoa(
            [
                'nome'      => $company->company_name,
                'endereco'  => $company->address,
                'cep'       => $company->cep,
                'uf'        => $company->city->state->uf,
                'cidade'    => $company->city->title,
                'documento' => $company->cnpj,
                'numero'    => $company->number,
                'complemento' => $company->complement ?? '',
            ]
        );

        $bankData = [
            'agencia'      => $bankAccount->agency_number,
            'conta'        => $bankAccount->account_number,
            'contaDv'      => $bankAccount->account_check_number ?? '',
            'beneficiario' => $recipient,
            'variacaoCarteira'     => '017',
            'convenio'     => '1111', //Validar
            'carteira'     => '11',
            'codigoFormaPagamento' => $requestInfo['code_cnab'],
            'tipoSeguimento' => $requestInfo['payment_type'],
        ];

        $allPaymentRequest = $this->paymentRequest->with($this->withPaymentRequest)->whereIn('id', $requestInfo['payment_request_ids'])->get();
        $billets = [];


        switch ($bankAccount->bank->bank_code) {
            case '341':
                $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Itau($bankData);
                break;
            case '001':
                $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Bb($bankData);
                break;
            default:
                return Response('Não é possível gerar CNAB com este código bancário.', 422);
        }

        foreach($allPaymentRequest as $paymentRequest) {

            $payer = new \App\Helpers\Pessoa(
                [
                    'nome'      => $paymentRequest->provider->provider_type == 'F' ? $paymentRequest->provider->full_name : $paymentRequest->provider->company_name,
                    'endereco'  => $paymentRequest->provider->address,
                    'cep'       => $paymentRequest->provider->cep,
                    'uf'        => $paymentRequest->provider->city->state->title,
                    'cidade'    => $paymentRequest->provider->city,
                    'documento' => $paymentRequest->provider->provider_type == 'F' ? $paymentRequest->provider->cpf : $paymentRequest->provider->cnpj,
                    'numero'    => $paymentRequest->provider->number,
                    'complemento' => $paymentRequest->provider->complement ?? '',
                ]
            );

            switch ($bankAccount->bank->bank_code) {
                case '341':
                    foreach($paymentRequest->installments as $installment) {
                        if($installment->codBank != 'BD') {
                            $billet = new \App\Helpers\Boleto\Banco\Itau(
                                [
                                    'dataVencimento'         => new Carbon($installment->due_date),
                                    'valor'                  => $installment->portion_amount ?? $paymentRequest->amount,
                                    'transferTypeIdentification' => $paymentRequest->form_payment ?? '',
                                    'numeroDocumento'        => $installment->id,
                                    'pagador'                => $payer,
                                    'beneficiario'           => $recipient,
                                    'agencia'                => $paymentRequest->bank_account_provider->agency_number ?? 0,
                                    'conta'                  => $paymentRequest->bank_account_provider->account_number ?? 0,
                                    'contaDv'                => $paymentRequest->bank_account_provider->account_check_number ?? 0,
                                    'codigoDeBarra'          => $paymentRequest->bar_code,
                                    'desconto'               => 0,
                                    'multa '                 => 0,
                                    'dataPagamento'          => new Carbon($paymentRequest->pay_date),
                                    'valorPagamento'       => $paymentRequest->amount,
                                ]
                            );
                        }
                        array_push($billets, $billet);
                    }
                    break;
                case '001':
                    foreach($paymentRequest->installments as $installment) {
                        if($installment->codBank != 'BD') {
                            $billet = new \App\Helpers\Boleto\Banco\Bb(
                                [
                                    'dataVencimento'         => new Carbon($installment->due_date),
                                    'valor'                  => $installment->portion_amount ?? $paymentRequest->amount,
                                    'transferTypeIdentification' => $paymentRequest->bank_account_provider->account_type ?? 3, // 3 - Pix
                                    'numeroDocumento'        => $installment->id,
                                    'pagador'                => $payer,
                                    'beneficiario'           => $recipient,
                                    'agencia'                => $paymentRequest->bank_account_provider->agency_number ?? 0,
                                    'conta'                  => $paymentRequest->bank_account_provider->account_number ?? 0,
                                    'contaDv'                => $paymentRequest->bank_account_provider->account_check_number ?? 0,
                                    'codigoDeBarra'          => $paymentRequest->bar_code,
                                    'desconto'               => 0,
                                    'multa '                 => 0,
                                    'dataPagamento'          => new Carbon($paymentRequest->pay_date),
                                    'valorPagamento'         => $paymentRequest->amount,
                                    'tipoDocumento'          => $paymentRequest->payment_type,
                                    'convenio'               => '1111', //Validar
                                    'agenciaDv'              => $paymentRequest->bank_account_provider->agency_check_number ?? '',
                                ]
                            );
                        }
                        array_push($billets, $billet);
                    }
                    break;
            }

        }

        $shipping->addBoletos($billets);
        $shipping->save();

        DB::table('accounts_payable_approval_flows')
        ->whereIn('payment_request_id', $requestInfo['payment_request_ids'])
        ->update(
            array(
                'status' => Config::get('constants.status.cnab generated')
            )
        );

        return response()->json([
            'linkArchive' => Storage::temporaryUrl('tempCNAB/cnab-remessa.txt', now()->addMinutes(5)),
        ]);
    }

    public function receiveCNAB240($requestInfo) {

        $returnFile = $requestInfo->file('return-file');

        $processArchive = new \App\Helpers\Cnab\Retorno\Cnab240\Banco\Itau($returnFile);
        $processArchive->processar();

        $returnArray = $processArchive->getDetalhes();

        foreach($returnArray as $batch){
            $installments =  $this->installments->findOrFail($batch->numeroDocumento);
            $installments->status = $batch->ocorrencia;
            $installments->codBank = $batch->nossoNumero;
            $installments->amount_received = $batch->valorRecebido;
            $installments->save();
        }

        $idParcelsAlreadyVerified = [];
        $idUnpaidPayment = [];
        $idPaidPayment = [];
        $idPaymentFinished = [];

        foreach($returnArray as $batch){

            if(!in_array($batch->numeroDocumento, $idParcelsAlreadyVerified)){
                $paymentRequest = PaymentRequest::with('installments')
                ->whereRelation('installments', 'id', '=', $batch->numeroDocumento)
                ->first();

                $thePaymentHasBeenPaid = true;

                foreach($paymentRequest->installments as $installment){
                    array_push($idParcelsAlreadyVerified, $installment->id);

                    if($installment->status != 'BD'){
                        $thePaymentHasBeenPaid = false;
                        if (!in_array($paymentRequest->id, $idUnpaidPayment)) {
                            array_push($idUnpaidPayment, $paymentRequest->id);
                        }
                    }
                }

                if($thePaymentHasBeenPaid == true){
                    if($paymentRequest->payment_type == 0){
                        array_push($idPaymentFinished, $paymentRequest->id);
                    }else {
                        array_push($idPaidPayment, $paymentRequest->id);
                    }
                }
            }
        }

        AccountsPayableApprovalFlow::whereIn('payment_request_id', $idPaidPayment)
        ->update(['status' => Config::get('constants.status.paid out')]);

        AccountsPayableApprovalFlow::whereIn('payment_request_id', $idPaymentFinished)
        ->update(['status' => Config::get('constants.status.finished')]);

        AccountsPayableApprovalFlow::whereIn('payment_request_id', $idUnpaidPayment)
        ->update(['status' => Config::get('constants.status.approved')]);

        return response()->json([
            'Sucesso' => 'Processo finalizado.',
        ], 200);
    }
}
