<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ResRobotWrapper;
use Sl\SlWrapper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SlWrapper::class, function ($app) {
            $wrapper = new SlWrapper();
            $wrapper->setTimeTablesApiKey(env('SL_TIMETABLE_KEY'));
            $wrapper->setRoutePlanningApiKey(env('SL_ROUTEPLANNING_KEY'));
            $wrapper->setStopLocationLookupApiKey(env('SL_STATIONLOOKUP_KEY'));
            $wrapper->setUserAgent(env('APP_USER_AGENT'));
            return new $wrapper;
        });

        $this->app->singleton(ResRobotWrapper::class, function ($app) {
            $wrapper = new ResRobotWrapper();
            $wrapper->setTimeTablesApiKey(env('RESROBOT_TIMETABLE_KEY'));
            $wrapper->setRoutePlanningApiKey(env('RESROBOT_ROUTEPLANNING_KEY'));
            $wrapper->setStopLocationLookupApiKey(env('RESROBOT_ROUTEPLANNING_KEY'));
            $wrapper->setUserAgent(env('APP_USER_AGENT'));
            return new $wrapper;
        });
    }
}
