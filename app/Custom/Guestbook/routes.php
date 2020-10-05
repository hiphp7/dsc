<?php

Route::prefix('guestbook')->group(function () {
    // pc
    Route::middleware('web')->group(function () {
        Route::get('/', 'IndexController@index')->name('index');
        Route::get('add', 'IndexController@add')->name('add');
        Route::post('save', 'IndexController@save')->name('save');
    });

    // mobile
    Route::middleware('web')->prefix('mobile')->group(function () {
        Route::get('/', 'MobileController@index')->name('mobile.index');
        Route::get('add', 'MobileController@add')->name('mobile.add');
        Route::post('save', 'MobileController@save')->name('mobile.save');
    });

    // api
    Route::middleware('api')->prefix('api')->group(function () {
        Route::get('/', 'ApiController@index')->name('api.index');
        Route::get('add', 'ApiController@add')->name('api.add');
        Route::post('save', 'ApiController@save')->name('api.save');
    });
});
