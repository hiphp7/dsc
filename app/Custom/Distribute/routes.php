<?php

/**
 * 分销
 */

// 平台后台
Route::group(['namespace' => 'Admin', 'prefix' => 'admin'], function () {
    // 继承 pc 修改
    Route::middleware('web')->prefix('drp')->group(function () {

    });

    // 独立 PC
    Route::middleware('web')->prefix('distribute')->group(function () {

        // 分销商配置 选择商品
        Route::any('select_goods', 'DrpController@select_goods')->name('admin.select_goods');
        // 选择商品 加入购买分销商商品
        Route::post('edit_goods', 'DrpController@edit_goods')->name('admin.edit_goods');
        // 分销商活动列表
        Route::any('activity_list', 'DrpController@activity_list')->name('admin.activity_list');
        // 设置分销活动状态
        Route::any('activity_finish', 'DrpController@activity_finish')->name('admin.activity_finish');
        // 删除活动
        Route::any('activity_remove', 'DrpController@activity_remove')->name('admin.activity_remove');
        // 活动详情页面
        Route::any('activity_details', 'DrpController@activity_details')->name('admin.activity_details');
        // 添加活动页面展示
        Route::any('activity_info', 'DrpController@activity_info')->name('admin.activity_info');
        // 添加活动操作
        Route::any('activity_info_add', 'DrpController@activity_info_add')->name('admin.activity_info_add');
        // 分销商活动用户列表
        Route::any('user_activity_list', 'DrpController@user_activity_list')->name('admin.user_activity_list');
        // 删除用户参与活动记录
        Route::any('user_activity_remove', 'DrpController@user_activity_remove')->name('admin.user_activity_remove');
        // 导出分销商活动用户信息
        Route::any('user_activity_list_export', 'DrpController@user_activity_list_export')->name('admin.user_activity_list_export');
        // 导出分销商活动统计明细
        Route::any('activity_details_export', 'DrpController@activity_details_export')->name('admin.activity_details_export');
        // 发放分销商活动奖励
        Route::any('activity_grant_award', 'DrpController@activity_grant_award')->name('admin.activity_grant_award');

        // 分销商提现申请记录
        Route::any('transfer_log', 'DrpController@transfer_log')->name('admin.transfer_log');
        // 分销商提现申请审核页面
        Route::any('transfer_log_check', 'DrpController@transfer_log_check')->name('admin.transfer_log_check');
        // 查看已提现结果
        Route::post('transfer_log_see', 'DrpController@transfer_log_see')->name('admin.transfer_log_see');
        // 分销商提现记录删除
        Route::post('transfer_log_delete', 'DrpController@transfer_log_delete')->name('admin.transfer_log_delete');
        // 分销商提现申请导出
        Route::post('export_transfer_log', 'DrpController@export_transfer_log')->name('admin.export_transfer_log');
        // 分销商排行导出
        Route::post('drp_list_export', 'DrpController@drp_list_export')->name('admin.drp_list_export');

    });

});

// 商户后台
Route::group(['namespace' => 'Seller', 'prefix' => 'seller'], function () {

    // 独立 PC
    Route::middleware('web')->prefix('distribute')->group(function () {

        // 分销商活动列表
        Route::any('activity_list', 'DrpController@activity_list')->name('seller.activity_list');
        // 设置分销活动状态
        Route::any('activity_finish', 'DrpController@activity_finish')->name('seller.activity_finish');
        // 活动详情页面
        Route::any('activity_details', 'DrpController@activity_details')->name('seller.activity_details');
        // 添加活动页面展示
        Route::any('activity_info', 'DrpController@activity_info')->name('seller.activity_info');
        // 添加活动操作
        Route::any('activity_info_add', 'DrpController@activity_info_add')->name('seller.activity_info_add');
        // 删除活动操作
        Route::any('activity_remove', 'DrpController@activity_remove')->name('seller.activity_remove');

    });

});


// 前台
Route::group(['prefix' => '/'], function () {

    // pc
    Route::middleware('web')->prefix('/')->group(function () {
        // 测试
        Route::get('distribute/test', 'TestController@test')->name('test');

    });

    // mobile
    Route::middleware('web')->prefix('distribute/mobile')->group(function () {
        //Route::get('/', 'MobileController@index')->name('mobile.index');
    });
});

// api
Route::group(['namespace' => 'Api', 'prefix' => 'api'], function () {

    // 继承 修改原api
    Route::middleware('api')->prefix('v4')->group(function () {

        // 分销 -- 佣金转出页面
        Route::get('drp/trans', 'DistributionController@trans')->name('api.drp.trans');
    });

    // 独立 api
    Route::middleware('api')->prefix('distribute')->group(function () {
        // 分销商 分享统计甄选师
        Route::post('drp_invite_info', 'DistributionController@drp_invite_info')->name('api.drp_invite_info');
        // 分销 下级分销商分成佣金列表
        Route::post('drp_invite_list', 'DistributionController@drp_invite_list')->name('api.drp_invite_list');


        // 分销商 佣金申请提现
        Route::post('deposit_apply', 'DistributionController@deposit_apply')->name('api.deposit_apply');
        // 分销商 我的申请提现记录
        Route::post('deposit_apply_list', 'DistributionController@deposit_apply_list')->name('api.deposit_apply_list');
        // 分销活动 -- 获取当前所有分销商活动
        Route::post('all_activity', 'DrpActivityController@all_activity')->name('api.all_activity');
        // 分销活动 -- 分销商领取分销商活动
        Route::post('user_draw_activity', 'DrpActivityController@user_draw_activity')->name('api.user_draw_activity');
        // 分销活动 -- 获取分销活动详情
        Route::post('get_activity_details', 'DrpActivityController@get_activity_details')->name('api.get_activity_details');
        // 分销活动 -- 前台分享处理
        Route::post('share_goods_activity', 'DrpActivityController@share_goods_activity')->name('api.share_goods_activity');
        // 分销活动 -- 分享订单结算操作处理
        Route::post('pay_order_activity', 'DrpActivityController@pay_order_activity')->name('api.pay_order_activity');
        // 分销活动 -- 退款订单操作处理
        Route::post('repeal_order_activity_dispose', 'DrpActivityController@repeal_order_activity_dispose')->name('api.repeal_order_activity_dispose');
        // 分销商 -- 获取分销商升级条件
        Route::post('drp_user_upgrade_condition', 'DrpActivityController@drp_user_upgrade_condition')->name('api.drp_user_upgrade_condition');

    });
});
