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
        'App\Console\Commands\BackupDatabase',
        'App\Console\Commands\CacheHome',
        'App\Console\Commands\CacheGroceries',
        'App\Console\Commands\PromotionExpired',
        'App\Console\Commands\SaleExpired',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('backup:database')->daily()->at('05:00')->runInBackground();
        $schedule->command('cache:home')->mondays()->at('06:00')->runInBackground();
        $schedule->command('cache:groceries')->weekly()->sundays()->at('03:00')->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
