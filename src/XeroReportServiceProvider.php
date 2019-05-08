<?php

namespace Nosok\XeroReport;

use Illuminate\Support\ServiceProvider;
use Nosok\XeroReport\Commands\CreateReport;

class XeroReportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // copy config file to specified location on vendor:publish command,
        $this->publishes([
            __DIR__ . '/../config/xeroreport.php' => config_path('xeroreport.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/xeroreport.php', 'xeroreport');

        $this->app->bind('command.xero:report', CreateReport::class);
        $this->commands(['command.xero:report']);
    }
}
