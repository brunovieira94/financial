<?php

namespace App\Http\Resources\Integrations;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Exports\Utils as ExportUtils;
use App\Helpers\Tools;

class ApprovedPaymentRequestsSAPResource extends JsonResource
{
    public $preserveKeys = false;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $last_approval_log = $this->log_approval_flow->where('id', $this->log_approval_flow->max('id'))->first();
        $date_approval = isset($last_approval_log) ?  $last_approval_log['created_at']->format('Y-m-d') : '';

        return [
            'OPCH' => [
                'TipOP' => "A",
                'U_RBH_IdTransOrig' => $this->id,
                'VATRegNum' => isset($this->company) ? $this->company->cnpj : '',
                'DocDate' => $date_approval,
                'TaxDate' => $this->emission_date,
                'GroupNum' => "-1",
                'DocType' => "S",
                'CtlAccount' => $this->chart_of_accounts->code ?? "",
                'JrnlMemo' => $this->chart_of_accounts->title ?? "",
                'Installmnts' => $this->installments->count(),
                'TipDoc' => $this->docTypeToSAP($this->payment_type),
                'DocCur' => $this->currency->currency_symbol ?? "R$",
                'DocRate' => $this->exchange_rate ?? "",
                'PCH1' => $this->purchaseOrderMetaResources($this->purchase_order),
                'PCH6' => $this->installmentsMetaResource($this->installments),
                'OCRD' => [
                    'U_RBH_CodPnSisOrig' => $this->provider->id,
                    'CardType' => "S",
                    'CardName' => $this->provider->company_name ?? "",
                    'AliasName' => ExportUtils::providerAlias($this),
                    'Currency' => "##",
                    'Phone1' => $this->providerPhoneNNum(1),
                    'Phone2' => $this->providerPhoneNNum(2),
                    'E_mail' => $this->provider->email ?? "",
                    'OCPR' => [
                        'Name' => $this->provider->responsible ?? "",
                        'Tel1' => $this->provider->responsible_phone ?? "",
                        'E_mailL' => $this->provider->responsible_email ?? "",
                    ],
                    'CRD1' => [
                        'Fin_Address' => "FINANCEIRO",
                        'Fin_AddrType' => "",
                        'Fin_Street' => $this->provider->address ?? "",
                        'Fin_StreetNo' => $this->provider->number ?? "SN",
                        'Fin_Building' => $this->provider->complement ?? "",
                        'Fin_ZipCode' => $this->provider->cep ?? "",
                        'Fin_Block' => "",
                        'Fin_City' => $this->providerCity(),
                        'Fin_County' => $this->provider->district,
                        'Fin_State' => $this->providerState(),
                        'Fin_Country' => $this->providerCountry(),
                        'Tax_Address' => "FISCAL",
                        'Tax_AddrType' => "",
                        'Tax_Street' => $this->provider->address ?? "",
                        'Tax_StreetNo' => $this->provider->number ?? "SN",
                        'Tax_Building' => $this->provider->complement ?? "",
                        'Tax_ZipCode' => $this->provider->cep ?? "",
                        'Tax_Block' => "",
                        'Tax_City' => $this->providerCity(),
                        'Tax_County' => $this->provider->district ?? "",
                        'Tax_State' =>  $this->providerState(),
                        'Tax_Country' => $this->providerCountry(),
                    ],
                    'CRD7' => [
                        'TaxId0' => $this->provider->cnpj ?? "",
                        'TaxId4' => $this->provider->cpf ?? "",
                        'TaxId1' => $this->provider->state_subscription ?? "Isento",
                        'TaxId3' => $this->provider->city_subscription ?? "Isento",
                    ],
                ],
            ],
        ];
    }

    private function purchaseOrderMetaResources($purchase_orders)
    {
        $res = [];
        $i = 0;

        foreach ($purchase_orders as $p) {
            $purchase_order = $p->purchase_order;
            $mainCostCenter = null;

            if (isset($purchase_order->cost_centers)) {
                $mainCostCenter = $purchase_order->cost_centers->where('percentage', $purchase_order->cost_centers->max('percentage'));
            }

            array_push($res, [
                'LineNum' => $i,
                'AcctCode' => $purchase_order->provider->chart_of_account->code ?? "",
                'LineTotal' => $purchase_order->installments_total_value ?? 0,
                'TaxCode' => "OutrasOP",
                'OcrCode2' => "",
                'OcrCode' => isset($mainCostCenter) ? $mainCostCenter->first()->id : null,
            ]);

            $i += 1;
        }

        return $res;
    }

    private function providerCity()
    {
        return Tools::getOrElse($this, 'provider.city.title', '');
    }

    private function providerState()
    {
        return Tools::getOrElse($this, 'provider.city.state.title', '');
    }

    private function providerCountry()
    {
        return Tools::getOrElse($this, 'provider.city.state.country.title', '');

    }

    private function installmentsMetaResource($installments)
    {
        $res = [];
        $total = $installments->count();

        foreach ($installments as $installment) {
            $insTotal = ExportUtils::installmentTotalFinalValue($installment);

            array_push($res, [
                'TotalParcelas' => $total,
                'InstlmntID' => $installment->parcel_number,
                'DueDate' => $installment->due_date,
                'InsTotal' => round($insTotal, 2),
            ]);
        }

        return $res;
    }

    private function docTypeToSAP($paymentType)
    {
        if (is_null($paymentType)) {
            return '';
        }

        return $paymentType == 4 ? 'DARF' : 'OUTROS';
    }

    private function providerPhoneNNum($n)
    {
        $phones = $this->provider->phones;
        return is_array($phones) && $n > 0 && $n <= count($phones) ? $phones[$n - 1] : '';
    }
}
