<?php

Route::prefix(SUPPLLY_PATH)->group(function () {
    Route::redirect('/', '/' . SUPPLLY_PATH . '/index.php');
    Route::any('admin_logs.php', 'AdminLogsController@index')->name('supplier.adminlogs');
    Route::any('attribute.php', 'AttributeController@index')->name('supplier.attribute');
    Route::any('dialog.php', 'DialogController@index')->name('supplier.dialog');
    Route::any('gallery_album.php', 'GalleryAlbumController@index')->name('supplier.gallery_album');
    Route::any('get_ajax_content.php', 'GetAjaxContentController@index')->name('supplier.ajax_content');
    Route::any('get_password.php', 'GetPasswordController@index')->name('supplier.get_password');
    Route::any('goods.php', 'GoodsController@index')->name('supplier.goods');
    Route::any('goods_type.php', 'GoodsTypeController@index')->name('supplier.goods_type');
    Route::any('index.php', 'IndexController@index')->name('supplier.home');
    Route::any('order.php', 'OrderController@index')->name('supplier.order');
    Route::any('privilege.php', 'PrivilegeController@index')->name('supplier.privilege');
    Route::any('privilege_suppliers.php', 'PrivilegeSuppliersController@index')->name('supplier.privilege_suppliers');
    Route::any('region.php', 'RegionController@index')->name('supplier.region');
    Route::any('suppliers_account.php', 'SuppliersAccountController@index')->name('supplier.account');
    Route::any('suppliers_commission.php', 'SuppliersCommissionController@index')->name('supplier.commission');
    Route::any('suppliers_sale_list.php', 'SuppliersSaleListController@index')->name('supplier.sale');
    Route::any('suppliers_stats.php', 'SuppliersStatsController@index')->name('supplier.stats');
    Route::any('tp_api.php', 'TpApiController@index')->name('supplier.tpapi');
    Route::any('editor.php', 'EditorController@index');
    Route::any('sms.php', 'SmsController@index');

    // 过滤词
    Route::prefix('filter')->name('suppliers/filter/')->group(function () {
        // 记录
        Route::any('updatelogs', 'FilterWordsController@updatelogs')->name('updatelogs');
    });
});
