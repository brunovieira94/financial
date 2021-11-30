<?php

namespace App\Services;
use App\Models\BillToPay;
use App\Models\Company;
use Eduardokum;

class CNABService
{
    private $billToPay;
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];
    private $withBillToPay = ['approval', 'installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(BillToPay $billToPay, Company $company)
    {
        $this->billToPay = $billToPay;
        $this->company = $company;
    }

    public function generateCNAB240Shipping($requestInfo)
    {
        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = null;

        foreach($company->bank_account as $bank) {
            if ($bank->id == $requestInfo['bank_account_id'])
                $bankAccount = $bank;
        }

        $recipient = new \Eduardokum\LaravelBoleto\Pessoa(
            [
                'nome'      => $company->company_name,
                'endereco'  => $company->address,
                'cep'       => $company->cep,
                'uf'        => $company->city->state->title,
                'cidade'    => $company->city->title,
                'documento' => $company->cnpj,
                'numero' => $company->number,
                'complemento' => $company->complement,
            ]
        );

        $shipping = new \Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco\Itau(
            [
                'agencia'      => $bankAccount->agency_number,
                'conta'        => $bankAccount->account_number,
                'contaDv'      => $bankAccount->account_check_number,
                'carteira'     => $bankAccount->wallet,
                'beneficiario' => $recipient,
            ]
        );

        $allBillToPay = $this->billToPay->with($this->withBillToPay)->whereIn('id', $requestInfo['bill_to_pay_ids'])->get();

        $billets = [];

        foreach($allBillToPay as $billToPay) {

            $payer = new \Eduardokum\LaravelBoleto\Pessoa(
                [
                    'nome'      => $billToPay->provider->company_name,
                    'endereco'  => $billToPay->provider->address,
                    'cep'       => $billToPay->provider->cep,
                    'uf'        => $billToPay->provider->city->state->title,
                    'cidade'    => $billToPay->provider->city,
                    'documento' => $billToPay->provider->provider_type == 'F' ? $billToPay->provider->cpf : $billToPay->provider->cnpj,
                    'numero' => $billToPay->provider->number,
                    'complemento' => $billToPay->provider->complement,
                ]
            );

            $billet = new Eduardokum\LaravelBoleto\Boleto\Banco\Itau(
                [
                    //'logo'                   => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '341.png',
                    'dataVencimento'         => new \Carbon\Carbon(),
                    'valor'                  => 100.50,
                    //'multa'                  => false,
                    //'juros'                  => false,
                    'numero'                 => 1,
                    'numeroDocumento'        => 1,
                    'pagador'                => $payer,
                    'beneficiario'           => $recipient,
                    'diasBaixaAutomatica'    => 2,
                    //'carteira'               => 112,
                    //'agencia'                => 1111,
                    //'conta'                  => 99999,
                    //'contaDv'                  => 5,
                    //'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
                    //'instrucoes'             => ['instrucao 1', 'instrucao 2', 'instrucao 3'],
                    //'aceite'                 => 'S',
                    //'especieDoc'             => 'DM',
                ]
            );
            array_push($billets, $billet);
        }

        $shipping->addBoletos($billets);
        return $shipping->save('/var/www/html/storage' . DIRECTORY_SEPARATOR . 'itau.txt');
    }
}
