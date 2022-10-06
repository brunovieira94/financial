<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;

class HotelReasonsToReject extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_reasons_to_reject', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });

        $module = Module::where('title', 'Faturamento de Hotéis')->first();
        DB::table('module')->insert([
            [
                'title'     => 'Motivos Para Rejeitar',
                'route' => 'hotel-reason-to-reject',
                'parent' => $module->id,
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
        Schema::dropIfExists('hotel_reasons_to_reject');
        $module = Module::where(['route' => 'hotel-reason-to-reject'])->first();
        Module::find($module->id)->delete();
    }
}
