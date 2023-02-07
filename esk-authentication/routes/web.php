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
// $router->get("/getDistrict[/{id}]", "DistrictController@getDistrict");
$router->post("/sendOtp", "AuthController@sendOtp");
$router->post("/validateOtp", "AuthController@validateOtp");
$router->post('/login', 'AuthController@login');
$router->post('/tokenTTL', 'AuthController@tokenTTL');

$router->post('/sendPwd', 'AuthController@sendPwd');
$router->post('/resetPassword', 'AuthController@resetPassword');
$router->post('/changePassword', 'AuthController@changePassword');


$router->group(
    [
        'middleware' => 'auth',
    ],
    function ($router) {
        $router->post('/logout', 'AuthController@logout');
        $router->get('/refresh', 'AuthController@refresh');
        $router->post('/refresh', 'AuthController@refresh');
    }
);

$router->post('/verifyLinkPermission', 'VerificationController@verifyLinkPermission');

$router->get('/key', function () use ($router) {
    return \Illuminate\Support\Str::random(32);
});



// NOTE: testing purposes
$router->post('/testOauth', 'AuthController@testOauth');