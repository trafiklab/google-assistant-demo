<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Trafiklab\Common\Model\Contract\PublicTransportApiWrapper;
use Trafiklab\ResRobot\ResRobotWrapper;
use Trafiklab\Sl\SlWrapper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*
        Here we define which implementation of PublicTransportApiWrapper should be returned when
        app(PublicTransportApiWrapper::class) is called. On the first call, a new instance will be created. On
        successive calls, the first instance will be re-used.

        This approach offers 2 large advantages:
        - We can easily change the used implementation, for example when we want to use different APIs.
        - We don't have to initialize the wrappers over and over again

        Read https://laravel.com/docs/5.8/container for more information.
        */
        $this->app->singleton(PublicTransportApiWrapper::class, function ($app) {
            // The API wrapper is determined by the environment variable 'APP_DATAPROVIDER'. If nothing is specified,
            // ResRobot will be used.
            if (env('APP_DATAPROVIDER') == "SL") {
                // Create an instance of SlWrapper.
                $wrapper = new SlWrapper();
                // Configure API keys
                $wrapper->setTimeTablesApiKey(env('SL_TIMETABLE_KEY'));
                $wrapper->setRoutePlanningApiKey(env('SL_ROUTEPLANNING_KEY'));
                $wrapper->setStopLocationLookupApiKey(env('SL_STATIONLOOKUP_KEY'));
                // Set the user agent, so the server knows which app sent the request.
                $wrapper->setUserAgent(env('APP_USER_AGENT'));
            } else {
                // Create an instance of ResRobotWrapper.
                $wrapper = new ResRobotWrapper();
                // Configure API keys
                $wrapper->setTimeTablesApiKey(env('RESROBOT_TIMETABLE_KEY'));
                $wrapper->setRoutePlanningApiKey(env('RESROBOT_ROUTEPLANNING_KEY'));
                // ResRobot uses the routeplanning API key for this
                $wrapper->setStopLocationLookupApiKey(env('RESROBOT_ROUTEPLANNING_KEY'));
                // Set the user agent, so the server knows which app sent the request.
                $wrapper->setUserAgent(env('APP_USER_AGENT'));
                return $wrapper;
            }
            return $wrapper;
        });
    }
}
