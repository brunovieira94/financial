<?php

use App\Models\Bank;
use App\Models\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCountryInBank extends Migration
{
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('country_bank_code')->nullable();
        });

        Schema::table('banks', function (Blueprint $table) {
            $table->integer('country_id')->unsigned()->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        $basilCountryID = Country::where('title', 'Brasil')->first();
        $basilCountryID = $basilCountryID == null ? null : $basilCountryID->id;
        Bank::whereNull('deleted_at')->update(['country_id' => $basilCountryID]);
    }

    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('country_bank_code');
        });
    }
}
