<?php

Route::prefix(STORES_PATH)->group(function () {
    Route::redirect('/', '/' . STORES_PATH . '/index.php');
    Route::any('dialog.php', 'DialogController@index');
    Route::any('get_password.php', 'GetPasswordController@index');
    Route::any('goods.php', 'GoodsController@index');
    Route::any('index.php', 'IndexController@index')->name('store.home');
    Route::any('order.php', 'OrderController@index');
    Route::any('privilege.php', 'PrivilegeController@index');
    Route::any('region.php', 'RegionController@index');
    Route::any('store_assistant.php', 'StoreAssistantController@index');
    Route::post('action.php', 'ActionController@index');

    // 过滤词
    Route::prefix('filter')->name('stores/filter/')->group(function () {
        // 记录
        Route::any('updatelogs', 'FilterWordsController@updatelogs')->name('updatelogs');
    });
});
