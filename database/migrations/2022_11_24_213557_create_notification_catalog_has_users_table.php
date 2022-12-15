<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationCatalogHasUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_catalog_has_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('notification_catalog_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->foreign('notification_catalog_id', 'nc_has_users_nc_id_foreign')->references('id')->on('notification_catalogs')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id', 'nc_has_user_id_foreign')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_catalog_has_users');
    }
}
