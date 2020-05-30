<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', 'LoginController@login');
Route::get('routes', 'RouteController@routeIndex');

Route::prefix('register')->group(function () {
    Route::post('admins', 'AdminController@adminRegister');
    Route::post('users', 'UserController@userRegister');
});

Route::middleware('auth.multi')->group(function () {

    Route::post('logout', 'LoginController@logout');
    Route::get('authCheck', 'LoginController@authCheck');
    Route::get('user/request', 'UserController@receiverSearch');

    Route::delete('routes/{route}', 'RouteController@routeDestroy');

    Route::prefix('waypoints')->group(function () {
        Route::get('/', 'WaypointController@waypointIndex');
        Route::post('/', 'WaypointController@waypointStore');
        Route::patch('{waypoint}', 'WaypointController@waypointUpdate');
        Route::delete('{waypoint}', 'WaypointController@waypointDestroy');
    });

    Route::prefix('orders')->group(function () {
        Route::get('/', 'OrderController@orderIndex');
        Route::post('/', 'OrderController@orderRegister');
        Route::get('check', 'OrderController@orderCheck');
        Route::get('show', 'OrderController@orderShow');
        Route::get('{cart}/orderAuth', 'OrderController@orderAuthentication');
    });

    Route::prefix('consent')->group(function () {
        Route::get('request', 'FcmController@consentRequest');
        Route::get('response', 'FcmController@consentResponse');
    });
});

Route::middleware('auth.node')->group(function () {
    Route::patch('orders/{order}', 'OrderController@orderUpdate');
    Route::post('routes/', 'RouteController@routeStore');
});
