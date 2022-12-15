<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationCatalogHasRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_catalog_has_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('notification_catalog_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->foreign('notification_catalog_id', 'nc_has_roles_nc_id_foreign')->references('id')->on('notification_catalogs')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id', 'nc_has_role_id_foreign')->references('id')->on('role')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_catalog_has_roles');
    }
}
