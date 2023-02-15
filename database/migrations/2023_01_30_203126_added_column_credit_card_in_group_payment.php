<?php

use App\Models\GroupFormPayment;
use App\Models\PaymentRequestClean;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnCreditCardInGroupPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (GroupFormPayment::where('title', 'CHAVE PIX')->exists()) {
            $groupPayment = GroupFormPayment::where('title', 'CHAVE PIX')->first();
            $idGroupPayment = $groupPayment->id;

            $groupPayment->delete();

            DB::table('payment_requests_installments')
                ->where('group_form_payment_id', $idGroupPayment)
                ->update(['group_form_payment_id' => 2]);

            DB::table('other_payments')
                ->where('group_form_payment_id', $idGroupPayment)
                ->update(['group_form_payment_id' => 2]);
        }

        Schema::table('group_form_payment', function (Blueprint $table) {
            $table->dropColumn('main_payment');
        });

        DB::table('group_form_payment')->insert([
            'title' => 'Cartão de Crédito',
        ]);
    }

    public function down()
    {
        Schema::table('group_form_payment', function (Blueprint $table) {
            $table->boolean('main_payment')->default(true);
        });
    }
}
