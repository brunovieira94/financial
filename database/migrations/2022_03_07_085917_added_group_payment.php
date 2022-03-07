<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedGroupPayment extends Migration
{
    public function up()
    {
        Schema::create('group_form_payment', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
        });

        DB::table('group_form_payment')->insert([
            [
                'title'     => 'Boleto',
            ],
            [
                'title'     => 'PIX',
            ],
            [
                'title'     => 'DOC',
            ],
            [
                'title'     => 'TED',
            ],
            [
                'title'     => 'DÉBITO EM CONTA',
            ],
        ]);

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->integer('group_form_payment_id')->unsigned()->nullable();
            $table->foreign('group_form_payment_id')->references('id')->on('group_form_payment')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        DB::table('payment_requests')
        ->where('payment_type', '!=', 1)
        ->update(['group_form_payment_id' => 2]);
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign('payment_requests_group_form_payment_id_foreign');
            $table->dropColumn('group_form_payment_id');
        });

        Schema::dropIfExists('group_form_payment');
    }
}
