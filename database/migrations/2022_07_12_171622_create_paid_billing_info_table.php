<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;

class CreatePaidBillingInfoTable extends Migration
{
    public function up()
    {
        Schema::create('paid_billing_info', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reserve')->nullable();
            $table->string('operator')->nullable();
            $table->float('supplier_value')->nullable();
            $table->float('boleto_value')->nullable();
            $table->dateTime('pay_date')->nullable();
            $table->string('boleto_code')->nullable();
            $table->string('remark')->nullable();
            $table->string('oracle_protocol')->nullable();
            $table->string('bank')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('form_of_payment')->nullable();
            $table->string('hotel_name')->nullable();
            $table->string('cnpj_hotel')->nullable();
            $table->string('payment_voucher')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_bank')->nullable();
            $table->string('payment_remark')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        $module = Module::where('title', 'Faturamento de Hotéis')->first();

        DB::table('module')->insert([
            [
                'title'     => 'Pagamentos',
                'route' => 'paid-billing-info',
                'parent' => $module->id,
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('paid_billing_info');
    }
}
