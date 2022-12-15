<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationCatalogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_catalogs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('type')->unique();
            $table->boolean('active')->default(true);
            $table->integer('schedule')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        $module = Module::where('title', 'Configurações')->first();
        DB::table('module')->insert([
            [
                'title' => 'Notificações',
                'route' => 'notifications',
                'parent' => $module->id
            ]
        ]);

        //default notifications
        DB::table('notification_catalogs')->insert([
            [
                'title' => 'Pedido de compras chega na alçada de aprovação',
                'type' => 'purchase-order-to-approve',
                'schedule' => 0,
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ],
            [
                'title' => 'Pedido de compras é totalmente aprovado',
                'type' => 'purchase-order-fully-approved',
                'schedule' => 0,
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ],
            [
                'title' => 'Notas Fiscais é vinculada à uma parcela do pedido de compras',
                'type' => 'purchase-order-vinculated',
                'schedule' => 0,
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ],
            [
                'title' => 'Pedido de compras de serviço atinge o "tempo de aviso para renovação"',
                'type' => 'purchase-order-renovation',
                'schedule' => 1,
                'created_at' =>  \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_catalogs');
        $module = Module::where(['route' => 'notifications'])->first();
        Module::find($module->id)->delete();
    }
}
