<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequestHasTax;
use App\Models\TypeOfTax;
use DB;
use Illuminate\Http\Request;


class InfoController extends Controller
{
    public function duplicateInformationSystem(Request $request)
    {
        $resume = DB::select("SELECT provider_id, invoice_number, count(*) total FROM payment_requests where invoice_number is not null AND deleted_at IS NULL group by provider_id, invoice_number having count(*) > 1;");
        $details = DB::select("SELECT id, provider_id, invoice_number, created_at, user_id, cost_center_id, amount
                FROM payment_requests
                where concat(provider_id, '-', invoice_number) IN (
                SELECT concat(provider_id, '-', invoice_number)
                FROM payment_requests where invoice_number is not null AND deleted_at IS NULL group by provider_id, invoice_number having count(*) > 1 );");

        $cpfDuplicate = DB::select("SELECT cpf, count(*) total FROM providers where cpf is not null AND deleted_at IS NULL group by cpf having count(*) > 1;");
        $cnpjDuplicate = DB::select("SELECT cnpj, count(*) total FROM providers where cnpj is not null AND deleted_at IS NULL group by cnpj having count(*) > 1;");
        $taxDuplicate = DB::select("SELECT * FROM type_of_tax;");

        return response()->json([
            'resumo' => $resume,
            'detalhes' => $details,
            'cnpj' => $cnpjDuplicate,
            'cpf' => $cpfDuplicate,
            'tax' => $taxDuplicate
        ], 200);
    }

    public function taxDelete(Request $request)
    {
        PaymentRequestHasTax::whereIn('type_of_tax_id', [9])->update(['type_of_tax_id' => 2]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [10])->update(['type_of_tax_id' => 3]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [12])->update(['type_of_tax_id' => 5]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [14])->update(['type_of_tax_id' => 7]);
        TypeOfTax::destroy([9,10,12,14]);
        PaymentRequestHasTax::whereIn('type_of_tax_id', [11,4])->update(['type_of_tax_id' => 15]);
        TypeOfTax::destroy([11,4]);

        return response()->json([
            'sucess' => 'taxas deletadas e atualizadas'
        ], 200);
    }

}
