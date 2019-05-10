<?php

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

$router->get('/dialogflow/intent/next-departure',
    [
        'uses' => 'NextDepartureController@getNextDeparture',
        'as' => 'getNextDeparture',
    ]
);

$app->get('/', function () {
    return 'Hello World! ' . env("APP_NAME")  . ' is up and running!';
});