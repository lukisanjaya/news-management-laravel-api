<?php

use Illuminate\Http\Request;
use App\User;
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


Route::prefix('category')->group(function () {
    Route::get('/', 'Api\CategoryController@index');
    Route::get('{id}', 'Api\CategoryController@show');

    Route::middleware('auth:api')->group(function () {
        Route::post('/', 'Api\CategoryController@store');
        Route::put('{id}', 'Api\CategoryController@update');
        Route::delete('{id}', 'Api\CategoryController@destroy');
    });
});

Route::prefix('tag')->group(function () {
    Route::get('/', 'Api\TagController@index');
    Route::get('{id}', 'Api\TagController@show');

    Route::middleware('auth:api')->group(function () {
        Route::post('/', 'Api\TagController@store');
        Route::put('{id}', 'Api\TagController@update');
        Route::delete('{id}', 'Api\TagController@destroy');
    });
});

Route::group([

    'middleware' => 'api',
    'namespace'  => '\App\Http\Controllers\Api',
    'prefix'     => 'auth'

], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
});
