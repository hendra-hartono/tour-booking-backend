<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', ['uses' => 'UserController@register']);
    $router->post('login', ['uses' => 'UserController@login']);
});

$router->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($router) {
    $router->get('tours',  ['uses' => 'TourController@index']);
    $router->get('tours/{id}', ['uses' => 'TourController@show']);
    $router->post('tours', ['uses' => 'TourController@store']);
    $router->put('tours/{id}', ['uses' => 'TourController@update']);
    $router->delete('tours/{id}', ['uses' => 'TourController@destroy']);

    $router->get('bookings',  ['uses' => 'BookingController@index']);
    $router->get('bookings/{id}', ['uses' => 'BookingController@show']);
    $router->post('bookings', ['uses' => 'BookingController@store']);
    $router->put('bookings/{id}', ['uses' => 'BookingController@update']);

    $router->get('passengers',  ['uses' => 'BookingController@index_passenger']);
    $router->get('currentuser', ['uses' => 'UserController@currentuser']);
});
