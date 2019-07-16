<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/ebay','EbayAuthController@storeSummary')->name('ebay.home');
Route::get('/ebay/auth','EbayAuthController@getAuth')->name('ebay.auth.get');
Route::get('/returnToken','EbayAuthController@returnToken')->name('ebay.auth.return.token');
Route::get('/Received/ApplicationToken','EbayAuthController@saveAppToken');
Route::delete('/ebay','EbayAuthController@disconnect')->name('ebay.disconnect');
