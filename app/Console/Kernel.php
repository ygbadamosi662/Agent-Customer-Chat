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
        'App\Console\Commands\RequestDetails',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
        if (env('APP_ENV') == 'local') {
            // $schedule->call('App\Http\Controllers\Api\ServiceMonitorController@getServices', ['status' => 'inactive']);
            // $schedule->command('requestdetails:execute')->everyMinute();

            // $schedule->call('App\Http\Controllers\Api\Developer\NotificationController@index')->everyMinute();
            // $schedule->call('App\Http\Controllers\Api\Web\OnlineTransactions\TransactionController@paymentStatus')->everyminute();

            // $schedule->call('App\Http\Controllers\Api\OrdersController@sendOrdersList')->everyMinute();
            //$schedule->command('requestdetails:execute')->everyMinute();

            //$schedule->call('App\Http\Controllers\Api\DashboardController@saveYearAnalysis')->everyMinute(); 
            //$schedule->call('App\Http\Controllers\Api\DashboardController@sendAnalysisMail')->everyMinute();
           
            // $schedule->call('App\Http\Controllers\Api\OrdersController@sendMerchantUsers')->everyMinute();
            $schedule->command('requestdetails:execute')->everyFiveMinutes();


            return;
        }
        if (env('APP_ENV') == 'staging') {
            // $schedule->call('App\Http\Controllers\Api\Web\OnlineTransactions\TransactionController@paymentStatus')->everyminute();
            //$schedule->call('App\Http\Controllers\Api\Web\OnlineTransactions\TransactionController@paymentStatus')->everyminute();

            // $schedule->call('App\Http\Controllers\Api\OrdersController@sendMerchantUsers')->everyMinute();


            return;
        }

        if (env('APP_ENV') == 'production') {
            // $schedule->call('App\Http\Controllers\Api\Developer\NotificationController@index')->everyMinute();
              // $schedule->call('App\Http\Controllers\Api\DashboardController@saveYearAnalysis')->dailyAt('16:10');
        //     $schedule->call('App\Http\Controllers\Api\DashboardController@saveYearAnalysis')->dailyAt('23:00');
        //     $schedule->call('App\Http\Controllers\Api\DashboardController@sendtestAnalysisMail')->dailyAt('21:00');
        //     $schedule->call('App\Http\Controllers\Api\DashboardController@sendAnalysisMail')->dailyAt('00:30');
        // $schedule->call('App\Http\Controllers\Api\OrdersController@sendMerchantUsers')->everyMinute();

            $schedule->command('conversations:clear')->everyThirtyMinutes();
            $schedule->call('App\Http\Controllers\Api\OrdersController@sendOrdersList')->dailyAt('18:00');
            $schedule->call('App\Http\Controllers\Api\ServiceMonitorController@getServices',
            ['status' => 'active'])->hourly();
            $schedule->call('App\Http\Controllers\Api\ServiceMonitorController@getServices',
            ['status' => 'inactive'])->everyFiveMinutes();

            $schedule->command('reprocessbulkairtime:execute')->everyTwoMinutes();
            $schedule->command('requestdetails:execute')->everyFiveMinutes();
            $schedule->command('bulkairtime:execute')->dailyAt('08:00');

        }

      
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
