<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Billing;
use App\Models\BillingPayment;
use App\Models\Company;
use App\Models\CnabBilling;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Config;

use App\CNABLayoutsParserHotel\CnabParser\Parser\Layout;
use App\CNABLayoutsParserHotel\CnabParser\Model\Remessa;
use App\CNABLayoutsParserHotel\CnabParser\Output\RemessaFile;
use App\CNABLayoutsParserHotel\CnabParser\Remessa\Remessa as GerarRemessa;
use App\CNABLayoutsParserHotel\CnabParser\Retorno\Itau;
use App\Models\CnabGenerated;
use App\Models\CnabGeneratedHasPaymentRequests;
use App\Models\CnabPaymentRequestsHasInstallments;

class HotelCNABService
{
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];

    public function __construct(Billing $billing, Company $company, BillingPayment $billingPayment)
    {
        $this->billing = $billing;
        $this->billingPayment = $billingPayment;
        $this->company = $company;
    }


    public function cnabParse($requestInfo)
    {

        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = BankAccount::with('bank')->findOrFail($requestInfo['bank_account_id']);

        $allBillings = $this->billingPayment
            ->with(['billings'])
            ->whereIn('id', $requestInfo['billing_payments_ids'])
            ->get();

        foreach ($allBillings as $billingPayment) {
            if(!$billingPayment->ready_to_pay) return Response()->json([
                'error' => 'Existe(m) faturamento(os) inválidos para pagamento'
            ], 422);
        }

        // $allBillings = $this->billing
        //     ->with(['bank_account'])
        //     ->whereIn('id', $requestInfo['billings_ids'])
        //     ->get();

        //agrupar todos os pagamentos
        $allGroupedBilling = Utils::groupBillings(
            $allBillings,
            $bankAccount->bank->bank_code
        );

        switch ($bankAccount->bank->bank_code) {
            case '341':
                $remessaLayout = new Layout(app_path() . '/CNABLayoutsParserHotel/config/itau/cnab240/cobranca.yml');
                $remessa = new Remessa($remessaLayout);
                $remessa = GerarRemessa::gerarRemessaItau($remessa, $company, $bankAccount, $allGroupedBilling);
                break;
            default:
                return Response()->json([
                    'error' => 'banco selecionado pela empresa inválido.'
                ], 422);
        }

        // gera arquivo
        $remessaFile = new RemessaFile($remessa);
        $archiveName = $remessaFile->generate(app_path() . 'CNABLayoutsParserHotel/tests/out/bbcobranca240.rem');

        DB::table('billing_payments')
            ->whereIn('id', $requestInfo['billing_payments_ids'])
            ->update(
                array(
                    'status' => 2
                )
            );

        $cnabGenerated = CnabGenerated::create(
            [
                'user_id' => auth()->user()->id,
                'file_date' => Carbon::now()->format('Y/m/d H:i:s'),
                'file_name' => $archiveName
            ]
        );

        self::syncCnabGenerate($cnabGenerated, $requestInfo['billing_payments_ids']);

        return response()->json([
            'linkArchive' => Storage::disk('s3')->temporaryUrl("tempCNAB/{$archiveName}", now()->addMinutes(30))
        ], 200);
    }

    public function receiveCNAB240($requestInfo)
    {
        $arrayString = preg_split("/\r\n|\n|\r/", file_get_contents($requestInfo->file('return-file')));

        foreach ($arrayString as $line) {

            $billingPaymentStatusReturnCnab = null;

            $recordType = substr($line, 7, 1); // 3
            $segmentCode = substr($line, 13, 1); // A or J
            $billingPaymentID = trim(substr($line, 182, 20)); //id installment
            $codeReturn = trim(substr($line, 230, 2)); //id installment

            if ($recordType == '3') {
                if ($segmentCode == 'A' or $segmentCode == 'J') {
                    if (BillingPayment::where('id', (int)$billingPaymentID)->exists()) {

                        if (Itau::paymentDone($codeReturn)) {
                            Billing::where('billing_payment_id', $billingPaymentID)->update(['approval_status' => Config::get('billingStatus.status.paid out')]);
                            Utils::createPaiBillingInfo(Billing::where('billing_payment_id', $billingPaymentID)->with(['cangooroo','user', 'bank_account'])->get());
                            $billingPaymentStatusReturnCnab = 3;
                        } else {
                            $billingPaymentStatusReturnCnab = 1; // erro
                        }

                        $billingPayment = BillingPayment::findOrFail((int)$billingPaymentID);

                        $billingPayment->status = $billingPaymentStatusReturnCnab;
                        $billingPayment->status_cnab_code = $codeReturn;
                        $billingPayment->text_cnab = Itau::codeReturnItau($codeReturn);
                        $billingPayment->save();
                    }
                }
            }
        }

        return response()->json([
            'Sucesso' => 'Processo finalizado.',
        ], 200);
    }

    public function syncCnabGenerate($cnabGenerated, $allBillingPaymentsIds)
    {
        $allBillingPayments = $this->billingPayment->whereIn('id', $allBillingPaymentsIds)->get();
        foreach ($allBillingPayments as $billingPayment) {
            $cnabBillings = CnabBilling::create(
                [
                    'billing_payment_id' => $billingPayment->id,
                    'cnab_generated_id' => $cnabGenerated->id
                ]
            );
        }
    }
}
