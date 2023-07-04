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
use App\CNABLayoutsParser\CnabParser\Model\Retorno;
use App\CNABLayoutsParser\CnabParser\Input\RetornoFile;
use App\CNABLayoutsParser\CnabParser\Retorno\BancoBrasil;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\PaymentRequestClean;
use Faker\Core\Uuid;
use App\Models\PaymentRequestHasInstallmentLinked;

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
        $arrayString = preg_split("/\r\n|\n|\r/", file_get_contents($requestInfo->file('return-file')));
        $arrayInstallments = [];
        $arrayPaymentRequest = [];
        $arrayInstallmentsPayOut = [];
        $codeBankReturn = substr($arrayString[0], 0, 3);

        $headerDate = substr($arrayString[0], 143, 8);
        $headerTime = substr($arrayString[0], 151, 6);

        $cnabBankAccountCompany = null;
        if ((CnabGenerated::where('header_date', $headerDate)->where('header_time', $headerTime)->exists())) {
            $cnabGenerated = CnabGenerated::where('header_date', $headerDate)->where('header_time', $headerTime)->orderBy('id', 'DESC')->first();
            $cnabBankAccountCompany = $cnabGenerated->bank_account_company_id;
            $file = $requestInfo->file('return-file');
            $originalName  = explode('.', $file->getClientOriginalName());
            $extension = $originalName[count($originalName) - 1];
            $generatedName = 'return-' . uniqid(date('HisYmd')) . '.' . $extension;

            $file->storeAs(
                'tempCNAB',
                $generatedName,
                's3'
            );
            $cnabGenerated->archive_return = $generatedName;
            $cnabGenerated->save();
        }

        foreach ($arrayString as $line) {

            $recordType = substr($line, 7, 1); // 3
            $segmentCode = substr($line, 13, 1); // A or J
            $codeReturn = trim(substr($line, 230, 2)); //id installment

            if ($recordType == '3') {
                if ($segmentCode == 'A' or $segmentCode == 'J') {

                    $paymentDate = null;
                    $valuePayment = null;

                    if ($segmentCode == 'A') {
                        $installmentID = trim(substr($line, 73, 20));
                        $paymentDate = substr($line, 158, 4) . substr($line, 156, 2) . substr($line, 154, 2);
                        $valuePayment =  ltrim(substr($line, 162, 13), '0')  . '.' . substr($line, 175, 2);
                    } else if ($segmentCode == 'J') {
                        $installmentID = trim(substr($line, 182, 20));
                        $paymentDate = substr($line, 148, 4) . substr($line, 146, 2) . substr($line, 144, 2);
                        $valuePayment =  ltrim(substr($line, 152, 13), '0')  . '.' . substr($line, 165, 2);
                    }

                    //   dd($paymentDate, $installmentID, $valuePayment);

                    if (PaymentRequestHasInstallments::where('id', (int)$installmentID)->exists()) {
                        array_push($arrayInstallments, (int)$installmentID);

                        if (BancoBrasil::paymentDone($codeReturn)) {
                            $statusReturnCnab = Config::get('constants.status.paid out');
                        } else {
                            $statusReturnCnab = Config::get('constants.status.error');
                        }

                        $installment = PaymentRequestHasInstallments::findOrFail((int)$installmentID);

                        $installment->status = $statusReturnCnab;
                        $installment->status_cnab_code = $codeReturn;
                        $installment->text_cnab = BancoBrasil::codeReturnBrazilBank($codeReturn);
                        $installment->system_payment_method = 0;
                        $installment->group_form_payment_made_id = $installment->group_form_payment_id;
                        $installment->bank_account_company_id = $cnabBankAccountCompany;
                        $installment->paid_value = $valuePayment;
                        $installment->payment_made_date = $paymentDate;
                        $installment->save();

                        DB::table('accounts_payable_approval_flows')->where('payment_request_id', $installment->payment_request_id)
                            ->update(
                                [
                                    'status' => Config::get('constants.status.approved'),
                                ]
                            );

                        if (!in_array($installment->payment_request_id, $arrayPaymentRequest)) {
                            array_push($arrayPaymentRequest, $installment->payment_request_id);
                        }

                        if ($statusReturnCnab == Config::get('constants.status.paid out')) {
                            array_push($arrayInstallmentsPayOut, $installment->id);
                        }
                    }

                    foreach ($arrayPaymentRequest as $paymentRequestID) {
                        if (PaymentRequestClean::withoutGlobalScopes()->where('id', $paymentRequestID)->exists()) {
                            if (!PaymentRequestClean::withoutGlobalScopes()->where('id', $paymentRequestID)->whereHas('installments', function ($query) {
                                $query->where('status', '!=', Config::get('constants.status.paid out'));
                            })->exists()) {
                                AccountsPayableApprovalFlowClean::where('payment_request_id', $paymentRequestID)
                                    ->update(
                                        [
                                            'status' => Config::get('constants.status.paid out'),
                                        ]
                                    );
                            }
                        }
                    }

                    foreach ($arrayInstallmentsPayOut as $installmentID){
                        Utils::paiOutInstallmentLinked($installmentID);
                    }
                }
            }
        }

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

        $headerDate = Utils::formatCnab('9', date('dmY'), 8);
        $headerTime = Utils::formatCnab('9', date('His'), 6);

        switch ($bankAccount->bank->bank_code) {
            case '001':
                $remessaLayout = new Layout(app_path() . '/CNABLayoutsParser/config/bb/cnab240/cobranca.yml');
                $remessa = new Remessa($remessaLayout);
                $remessa = GerarRemessa::gerarRemessaBancoBrasil($remessa, $company, $bankAccount, $allGroupedInstallment, $requestInfo['installments_ids'], $headerDate, $headerTime);
                break;
            case '341':
                $remessaLayout = new Layout(app_path() . '/CNABLayoutsParser/config/itau/cnab240/cobranca.yml');
                $remessa = new Remessa($remessaLayout);
                $remessa = GerarRemessa::gerarRemessaItau($remessa, $company, $bankAccount, $allGroupedInstallment, $requestInfo['installments_ids'], $headerDate, $headerTime);
                break;
            case '033':
                $remessaLayout = new Layout(app_path() . '/CNABLayoutsParser/config/santander/cnab240/cobranca.yml');
                $remessa = new Remessa($remessaLayout);
                $remessa = GerarRemessa::gerarRemessaSantander($remessa, $company, $bankAccount, $allGroupedInstallment, $requestInfo['installments_ids'], $headerDate, $headerTime);
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
                if ($installment->status != Config::get('constants.status.cnab generated') && $installment->status != Config::get('constants.status.paid out')) {
                    $billsPaid = false;
                    break;
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
                'file_name' => $archiveName,
                'company_id' => $company->id,
                'bank_account_company_id' => $bankAccount->id,
                'header_date' => $headerDate,
                'header_time' => $headerTime,
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
