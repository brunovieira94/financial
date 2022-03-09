<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedGruoupPaymentInFormPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_payment', function (Blueprint $table) {
            $table->integer('group_form_payment_id')->unsigned()->nullable();
            $table->foreign('group_form_payment_id')->references('id')->on('group_form_payment')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        DB::table('form_payment')
              ->whereIn('title', ['DOC C - Outro Titular', 'DOC D - Mesmo Titular'])
              ->update(['group_form_payment_id' => 3]);

        DB::table('form_payment')
              ->whereIn('title', ['TED – Outro Titular', 'TED – Mesmo Titular', 'TED Outra Titularidade', 'TED Mesma Titularidade'])
              ->update(['group_form_payment_id' => 4]);

        DB::table('form_payment')
              ->whereIn('title', ['PIX Transferência', 'PIX QR-CODE', 'Pix Transferência', 'Pix QR­Code'])
              ->update(['group_form_payment_id' => 2]);

        DB::table('form_payment')
              ->whereIn('title', ['Boleto do Itaú', 'Boleto de Outros Bancos'])
              ->update(['group_form_payment_id' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_payment', function (Blueprint $table) {
            $table->dropForeign('form_payment_group_form_payment_id_foreign');
            $table->dropColumn('group_form_payment_id');
        });
    }
}
