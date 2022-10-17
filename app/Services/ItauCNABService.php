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

use App\CNABLayoutsParser\CnabParser\Parser\Layout;
use App\CNABLayoutsParser\CnabParser\Model\Remessa;
use App\CNABLayoutsParser\CnabParser\Output\RemessaFile;
use App\CNABLayoutsParser\CnabParser\Remessa\Remessa as GerarRemessa;
use App\Models\CnabGenerated;
use App\Models\CnabGeneratedHasPaymentRequests;
use App\Models\CnabPaymentRequestsHasInstallments;
use App\Models\PaymentRequestHasInstallmentsClean;

class ItauCNABService
{
    private $paymentRequest;
    private $installments;
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];
    private $withPaymentRequest = ['group_payment', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

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
            'convenio'     => $bankAccount->covenant ?? '',
            'codigoFormaPagamento' => $requestInfo['code_cnab'] ?? '',
            'tipoSeguimento' => $requestInfo['group_form_payment_id'],
        ];

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
                    'numero'    => $paymentRequest->provider->number,
                    'complemento' => $paymentRequest->provider->complement ?? '',
                ]
            );

            foreach ($paymentRequest->installments as $installment) {
                if (in_array($installment->id, $requestInfo['installments_ids'])) {
                    $billet =
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
                            'valorPagamento'         => $installment->portion_amount,
                            'tipoDocumento'          => $paymentRequest->payment_type,
                            'convenio'               => '1111', //Validar
                            'agenciaDv'              => $paymentRequest->bank_account_provider->agency_check_number ?? '',
                        ];

                    switch ($bankAccount->bank->bank_code) {
                        case '341':
                            $billetData = new \App\Helpers\Boleto\Banco\Itau($billet);
                            break;
                        case '001':
                            $billetData = new \App\Helpers\Boleto\Banco\Bb($billet);
                            break;
                        default:
                            return Response('Não é possível gerar CNAB com este código bancário.', 422);
                    }
                    array_push($billets, $billetData);
                }
            };
        }

        switch ($bankAccount->bank->bank_code) {
            case '341':
                $bankData['carteira'] = 112;
                $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Itau($bankData);
                break;
            case '001':
                $bankData['carteira'] = 11;
                $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Bb($bankData);
                break;
            default:
                return Response('Não é possível gerar CNAB com este código bancário.', 422);
        }

        $shipping->addBoletos($billets);
        $shipping->save();

        DB::table('payment_requests_installments')
            ->whereIn('id', $requestInfo['installments_ids'])
            ->update(
                array(
                    'status' => Config::get('constants.status.cnab generated')
                )
            );

        foreach ($allPaymentRequest as $paymentRequest) {
            $billsPaid = true;
            foreach ($paymentRequest->installments as $installment) {
                if ($installment->status != 6) {
                    $billsPaid = false;
                }
            }
            if ($billsPaid) {
                DB::table('accounts_payable_approval_flows')
                    ->where('payment_request_id', $paymentRequest->id)
                    ->update(
                        array(
                            'status' => Config::get('constants.status.cnab generated')
                        )
                    );
            }
        }


        return response()->json([
            'linkArchive' => Storage::disk('s3')->temporaryUrl('tempCNAB/cnab-remessa.txt', now()->addMinutes(30))
        ], 200);

        //$filename = 'cnab.txt';
        //$tempImage = tempnam(sys_get_temp_dir(), $filename);
        //copy(, $tempImage);
        //return response()->download($tempImage, $filename);
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

        $idParcelsAlreadyVerified = [];
        $idUnpaidPayment = [];
        $idPaidPayment = [];
        $idPaymentFinished = [];

        foreach ($returnArray as $batch) {

            if (!in_array($batch->numeroDocumento, $idParcelsAlreadyVerified)) {
                $paymentRequest = PaymentRequest::with('installments')
                    ->whereRelation('installments', 'id', '=', $batch->numeroDocumento)
                    ->first();

                $thePaymentHasBeenPaid = true;

                foreach ($paymentRequest->installments as $installment) {
                    array_push($idParcelsAlreadyVerified, $installment->id);

                    if ($installment->status != 'BD') {
                        $thePaymentHasBeenPaid = false;
                        if (!in_array($paymentRequest->id, $idUnpaidPayment)) {
                            array_push($idUnpaidPayment, $paymentRequest->id);
                        }
                    }
                }

                if ($thePaymentHasBeenPaid == true) {
                    if ($paymentRequest->payment_type == 0) {
                        array_push($idPaymentFinished, $paymentRequest->id);
                    } else {
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


    public function cnabParse($requestInfo)
    {
        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = BankAccount::with('bank')->findOrFail($requestInfo['bank_account_id']);

        if ($requestInfo['all']) {
            $installment = PaymentRequestHasInstallmentsClean::with('payment_request');
            $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('status', 1);
                });
                $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo['filters']);
            })->get();

            $requestInfo['installments_ids'] = $installment->pluck('id')->toArray();
            $requestInfo['payment_request_ids'] = array_unique($installment->pluck('payment_request_id')->toArray());
        }

        $allInstallments = $this->installments
            ->with(['payment_request', 'group_payment', 'bank_account_provider'])
            ->whereIn('id', $requestInfo['installments_ids'])
            ->get();

        //agrupar todos os pagamentos
        $allGroupedInstallment = Utils::groupInstallments(
            $allInstallments,
            $bankAccount->bank->bank_code
        );

        switch ($bankAccount->bank->bank_code) {
            case '001':
                $remessaLayout = new Layout(app_path() . '/CNABLayoutsParser/config/bb/cnab240/cobranca.yml');
                $remessa = new Remessa($remessaLayout);
                $remessa = GerarRemessa::gerarRemessaBancoBrasil($remessa, $company, $bankAccount, $allGroupedInstallment, $requestInfo['installments_ids']);
                break;
            case '341':
                $remessaLayout = new Layout(app_path() . '/CNABLayoutsParser/config/itau/cnab240/cobranca.yml');
                $remessa = new Remessa($remessaLayout);
                $remessa = GerarRemessa::gerarRemessaItau($remessa, $company, $bankAccount, $allGroupedInstallment, $requestInfo['installments_ids']);
                break;
            default:
                return Response()->json([
                    'error' => 'banco selecionado pela empresa inválido.'
                ], 422);
        }

        // gera arquivo
        $remessaFile = new RemessaFile($remessa);
        $archiveName = $remessaFile->generate(app_path() . 'CNABLayoutsParser/tests/out/bbcobranca240.rem');

        DB::table('payment_requests_installments')
            ->whereIn('id', $requestInfo['installments_ids'])
            ->update(
                array(
                    'status' => Config::get('constants.status.cnab generated')
                )
            );

        foreach ($this->paymentRequest
            ->with($this->withPaymentRequest)
            ->whereIn('id', $requestInfo['payment_request_ids'])
            ->get() as $paymentRequest) {
            $billsPaid = true;
            foreach ($paymentRequest->installments as $installment) {
                if ($installment->status != 6) {
                    $billsPaid = false;
                }
            }
            if ($billsPaid) {
                DB::table('accounts_payable_approval_flows')
                    ->where('payment_request_id', $paymentRequest->id)
                    ->update(
                        array(
                            'status' => Config::get('constants.status.cnab generated')
                        )
                    );
            }
        }

        $cnabGenerated = CnabGenerated::create(
            [
                'user_id' => auth()->user()->id,
                'file_date' => Carbon::now()->format('Y/m/d H:i:s'),
                'file_name' => $archiveName
            ]
        );

        self::syncCnabGenerate($cnabGenerated, $requestInfo['payment_request_ids'], $requestInfo['installments_ids']);

        return response()->json([
            'linkArchive' => Storage::disk('s3')->temporaryUrl("tempCNAB/{$archiveName}", now()->addMinutes(30))
        ], 200);
    }

    public function syncCnabGenerate($cnabGenerated, $allPaymentRequestID, $installments)
    {
        $allPaymentRequest = PaymentRequest::with('installments')->whereIn('id', $allPaymentRequestID)->get();
        foreach ($allPaymentRequest as $paymentRequest) {
            $cnabGeneratedHasPaymentRequests = CnabGeneratedHasPaymentRequests::create(
                [
                    'payment_request_id' => $paymentRequest->id,
                    'cnab_generated_id' => $cnabGenerated->id
                ]
            );

            foreach ($paymentRequest->installments as $installment) {
                if (in_array($installment->id, $installments)) {
                    CnabPaymentRequestsHasInstallments::create(
                        [
                            'payment_request_id' => $paymentRequest->id,
                            'cnab_generated_id' => $cnabGenerated->id,
                            'installment_id' => $installment->id,
                            'cnab_generated_has_payment_requests_id' => $cnabGeneratedHasPaymentRequests->id
                        ]
                    );
                }
            }
        }
    }
}
