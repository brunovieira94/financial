<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlutoTableStateHasColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pluto_table_state_has_columns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pluto_table_state_id')->unsigned();
            $table->text('field');
            $table->double('width')->nullable();
            $table->integer('position')->nullable();
            $table->boolean('fixed')->nullable();
            $table->boolean('visible')->nullable();
            $table->timestamps();
            $table->foreign('pluto_table_state_id')->references('id')->on('pluto_table_state')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pluto_table_state_has_columns');
    }
}
