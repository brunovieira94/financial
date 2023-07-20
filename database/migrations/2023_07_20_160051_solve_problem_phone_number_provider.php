<?php

use App\Models\Provider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveProblemPhoneNumberProvider extends Migration
{
    public function up()
    {
        Provider::where('id', 127)->update(['phones' => '[]']);
    }
}
