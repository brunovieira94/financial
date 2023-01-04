<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFormPaymentBank extends Migration
{
    public function up()
    {
        DB::table('form_payment')->insert(
            [
                [
                    'bank_code' => '033',
                    'title' => 'Boleto',
                    'group_form_payment_id' => 1,
                    'same_ownership' => true,
                    'code_cnab' => '11'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'Boleto',
                    'group_form_payment_id' => 1,
                    'same_ownership' => false,
                    'code_cnab' => '11'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'TED/DOC',
                    'group_form_payment_id' => 3,
                    'same_ownership' => true,
                    'code_cnab' => '03'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'TED/DOC',
                    'group_form_payment_id' => 3,
                    'same_ownership' => false,
                    'code_cnab' => '03'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'TED/DOC',
                    'group_form_payment_id' => 4,
                    'same_ownership' => true,
                    'code_cnab' => '03'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'TED/DOC',
                    'group_form_payment_id' => 4,
                    'same_ownership' => false,
                    'code_cnab' => '03'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'PIX',
                    'group_form_payment_id' => 2,
                    'same_ownership' => true,
                    'code_cnab' => '45'
                ],
                [
                    'bank_code' => '033',
                    'title' => 'PIX',
                    'group_form_payment_id' => 2,
                    'same_ownership' => false,
                    'code_cnab' => '45'
                ],
            ]
        );
    }
}
