<?php

namespace App\Console;

use App\Services\CAWebServices;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $service = new CAWebServices(env('CA_WSDL_URL'));
            $sid = $service->getSessionID(env('CA_USERNAME'), env('CA_PASSWORD'));
            if (!$sid) {
                Log::error('Schedule update menu tree failed!');
                return;
            }
            $service->getTreeMenu($sid);
            Log::info('Tree menu updated!');
        })->daily();
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
