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
        'App\Console\Commands\Cache\CacheHome',
        'App\Console\Commands\Cache\CacheGroceries',
        'App\Console\Commands\Expire\ExpirePromotion',
        'App\Console\Commands\Expire\ExpireSale',
        'App\Console\Commands\Expire\ExpireFlyer',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('expire:promotion')->daily()->at('00:00')->runInBackground();
        $schedule->command('expire:sale')->daily()->at('00:00')->runInBackground();
        $schedule->command('expire:flyer')->daily()->at('00:00')->runInBackground();

        $schedule->command('backup:database')->weekly()->at('05:00')->runInBackground();
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
