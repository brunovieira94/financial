<?php

use App\Models\NotificationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedNotificationInCatalog extends Migration
{
    public function up()
    {
        DB::table('notification_catalogs')->insert([
            [
                'title'     => 'Solicitação de pagamento chega na alçada de aprovação',
                'type' => 'payment-request-to-approve',
                'active' => 1,
                'schedule' => 0,
            ],
            [
                'title'     => 'Relatório de pagamentos a vencer',
                'type' => 'payment-request-due-report',
                'active' => 1,
                'schedule' => 1,
            ]
        ]);
    }
}
