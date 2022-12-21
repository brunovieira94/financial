<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\StatusUserUpdate::class,
        'App\Console\Commands\DailyApprovalsReport',
        'App\Console\Commands\DailyPurchaseOrderRenewal',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('php artisan alter:user-status')->dailyAt('03:00');
        //desativar mails agendados
        //$schedule->command('command:payment-request-approvals-email')
        //->dailyAt('06:00');
        //$schedule->command('command:purchase-order-renewal')->dailyAt('07:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
