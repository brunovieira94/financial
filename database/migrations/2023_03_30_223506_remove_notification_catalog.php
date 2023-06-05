<?php

use App\Models\NotificationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNotificationCatalog extends Migration
{
    public function up()
    {
        NotificationCatalog::where('type', 'payment-request-to-approve')
            ->delete();
    }
}
