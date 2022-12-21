<?php

namespace App\Imports;

use App\Models\Billing;
use App\Models\BillingPayment;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Config;


class SetPayBillingImport implements ToCollection, WithValidation, WithHeadingRow
{
    public $not_imported = 0;
    public $imported = 0;

    use Importable;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            if($row['pago'] == true || $row['pago'] == 'sim' || $row['pago'] == 'SIM' || $row['pago'] == 'Sim')
            {
                $billingPayment = BillingPayment::where('id', $row['id_do_pagamento'])->with(['billings'])->first();
                if($billingPayment && $billingPayment->ready_to_pay){
                    $billingPayment->status = 3;
                    $billingPayment->save();
                    foreach ($billingPayment->billings as $key => $billing) {
                        Billing::where('id',$billing->id)->update([
                            'approval_status' => Config::get('constants.billingStatus.paid out')
                        ]);
                    }
                    $this->imported++;
                }
                else $this->not_imported++;

                // $billings = Billing::where('reserve', $row['reserva'])::where('cangooroo_service_id', $row['servico'])->get();
                // foreach ($billings as $key => $billing) {
                //     $billingPayment = BillingPayment::where('id', $billing->billing_payment_id)->with(['billings'])->first();
                //     if($billingPayment && $billingPayment->ready_to_pay){
                //         $billingPayment->status = 3;
                //         foreach($billingPayment->billings as $value){
                //             if($value->approval_status != Config::get('constants.billingStatus.paid out') && $value->id != $billing->id){
                //                 $billingPayment->status = Config::get('constants.billingStatus.approved');
                //             }
                //         }
                //         $billingPayment->save();
                //     }
                //     else $this->not_imported++;
                // }
            }
            else
            {
                $this->not_imported++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'id_do_pagamento' => 'required|integer',
            'pago' => 'required',
        ];
    }

}
