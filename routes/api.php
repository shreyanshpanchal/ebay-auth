<?php

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

Route::middleware('api')->group(function() {

    Route::get('/token/init','EbayAuthController@step1');

        Route::get('/token/step2','EbayAuthController@step2');

    Route::get('/token/step3','EbayAuthController@step3')->name('reply.token');

    Route::get('/token/refresh/{refresh}','EbayAuthController@refreshToken');
});