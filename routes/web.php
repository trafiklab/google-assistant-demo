<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->post('/dialogflow/intent/next-departure',
    [
        'uses' => 'NextDepartureController@getNextDeparture',
        'as' => 'getNextDeparture',
    ]
);

$router->post('/dialogflow/intent/plan-route',
    [
        'uses' => 'RoutePlanningController@getRoutePlanning',
        'as' => 'getRoutePlanning',
    ]
);

$router->post('/dialogflow/intent/handle', [
        'uses' => 'DialogFlowController@redirectIntentToController',
        'as' => 'redirectIntent',
    ]
);

$router->get('/', function () {
    return 'Hello World! ' . env("APP_NAME") . ' is up and running! Use "' . route('redirectIntent')
        . " as your fulfillment URL in DialogFlow";
});