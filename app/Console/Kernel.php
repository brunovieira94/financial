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
        \App\Console\Commands\DailyApprovalsReport::class,
        \App\Console\Commands\DailyPurchaseOrderRenewal::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:user-status')->dailyAt('03:00')->onOneServer();
        $schedule->command('command:payment-request-approvals-email')
        ->dailyAt('06:00')->onOneServer();
        $schedule->command('command:purchase-order-renewal')->dailyAt('07:00')->onOneServer();
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
