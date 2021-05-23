<?php

namespace App\Console\Commands\Expire;

use Carbon\Carbon;
use App\Models\Flyer;
use Illuminate\Console\Command;

class ExpireFlyer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:flyer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired flyers from database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Deleting All Expired Flyers');
        Flyer::whereDate('valid_to', '<', Carbon::now())->delete();
    }
}
