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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:admin')->get('/admin', function (Request $request) {
    return $request->user();
});

Route::post('login', 'LoginController@login');
Route::post('logout', 'LoginController@logout');
Route::get('authCheck', 'LoginController@authCheck');

Route::prefix('register')->group(function () {
    Route::post('admins', 'AdminController@adminRegister');
    Route::post('users', 'UserController@userRegister');
});

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
    Route::patch('{order}', 'OrderController@orderConsentUpdate');
    Route::get('{cart}/orderAuth', 'OrderController@orderAuthentication');
});

Route::get('user/request', 'UserController@receiverSearch');
