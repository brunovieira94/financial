<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleHasModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_has_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('role_id')->unsigned();
            $table->foreign('role_id')->references('id')->on('role')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('module_id')->unsigned();
            $table->foreign('module_id')->references('id')->on('module')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('create')->nullable()->default(0);
            $table->tinyInteger('read')->nullable()->default(1);
            $table->tinyInteger('update')->nullable()->default(0);
            $table->tinyInteger('delete')->nullable()->default(0);
            $table->tinyInteger('import')->nullable()->default(0);
            $table->tinyInteger('export')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_has_modules');
    }
}
