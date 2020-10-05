<?php

Route::prefix(ADMIN_PATH)->group(function () {
    Route::redirect('/', '/' . ADMIN_PATH . '/index.php');
    Route::any('account_log.php', 'AccountLogController@index');
    Route::any('ad_position.php', 'AdPositionController@index');
    Route::any('admin_logs.php', 'AdminLogsController@index');
    Route::any('ads.php', 'AdsController@index');
    Route::any('adsense.php', 'AdsenseController@index');
    Route::any('affiliate.php', 'AffiliateController@index');
    Route::any('affiliate_ck.php', 'AffiliateCkController@index');
    Route::any('agency.php', 'AgencyController@index');
    Route::any('area_manage.php', 'AreaManageController@index');
    Route::any('article.php', 'ArticleController@index');
    Route::any('article_auto.php', 'ArticleAutoController@index');
    Route::any('articlecat.php', 'ArticlecatController@index');
    Route::any('attention_list.php', 'AttentionListController@index');
    Route::any('attribute.php', 'AttributeController@index');
    Route::any('auction.php', 'AuctionController@index');
    Route::any('baitiao_batch.php', 'BaitiaoBatchController@index');

    // 砍价模块
    Route::prefix('bargain')->name('admin/bargain/')->middleware('web', 'admin_priv:bargain_manage')->group(function () {
        Route::any('/', 'BargainController@index')->name('index');
        Route::any('addgoods', 'BargainController@addgoods')->name('addgoods');
        Route::any('searchgoods', 'BargainController@searchgoods')->name('searchgoods');
        Route::any('goodsinfo', 'BargainController@goodsinfo')->name('goodsinfo');
        Route::any('setattributetable', 'BargainController@setattributetable')->name('setattributetable');
        Route::any('filtercategory', 'BargainController@filtercategory')->name('filtercategory');
        Route::any('searchbrand', 'BargainController@searchbrand')->name('searchbrand');
        Route::any('editgoods', 'BargainController@editgoods')->name('editgoods');
        Route::any('removegoods', 'BargainController@removegoods')->name('removegoods');
        Route::any('bargainlog', 'BargainController@bargainlog')->name('bargainlog');
        Route::any('bargain_statistics', 'BargainController@bargain_statistics')->name('bargain_statistics');
    });

    Route::any('bonus.php', 'BonusController@index');
    Route::any('brand.php', 'BrandController@index');
    Route::any('captcha_manage.php', 'CaptchaManageController@index');
    Route::any('card.php', 'CardController@index');
    Route::any('category.php', 'CategoryController@index');
    Route::any('category_store.php', 'CategoryStoreController@index');
    Route::any('check_file_priv.php', 'CheckFilePrivController@index');
    Route::any('cloud.php', 'CloudController@index');
    Route::any('comment_manage.php', 'CommentManageController@index');
    Route::any('comment_seller.php', 'CommentSellerController@index');
    Route::any('complaint.php', 'ComplaintController@index');
    Route::any('coupons.php', 'CouponsController@index');
    Route::any('cron.php', 'CronController@index');
    Route::any('database.php', 'DatabaseController@index');
    Route::any('dialog.php', 'DialogController@index');
    Route::any('discuss_circle.php', 'DiscussCircleController@index');
    Route::any('ecjia_config.php', 'EcjiaConfigController@index');
    Route::any('edit_languages.php', 'EditLanguagesController@index');
    Route::any('email_list.php', 'EmailListController@index');
    Route::any('entry_criteria.php', 'EntryCriteriaController@index');
    Route::any('exchange_detail.php', 'ExchangeDetailController@index');
    Route::any('exchange_goods.php', 'ExchangeGoodsController@index');
    Route::any('favourable.php', 'FavourableController@index');
    Route::any('flashplay.php', 'FlashplayController@index');
    Route::any('flow_stats.php', 'FlowStatsController@index');
    Route::any('friend_link.php', 'FriendLinkController@index');
    Route::any('friend_partner.php', 'FriendPartnerController@index');
    Route::any('gallery_album.php', 'GalleryAlbumController@index');
    Route::any('gen_goods_script.php', 'GenGoodsScriptController@index');
    Route::any('get_ajax_content.php', 'GetAjaxContentController@index');
    Route::any('get_password.php', 'GetPasswordController@index');
    Route::any('gift_gard.php', 'GiftGardController@index');
    Route::any('goods.php', 'GoodsController@index');
    Route::any('goods_area_attr.php', 'GoodsAreaAttrController@index');
    Route::any('goods_area_attr_batch.php', 'GoodsAreaAttrBatchController@index');
    Route::any('goods_area_batch.php', 'GoodsAreaBatchController@index');
    Route::any('goods_attr_price.php', 'GoodsAttrPriceController@index');
    Route::any('goods_auto.php', 'GoodsAutoController@index');
    Route::any('goods_batch.php', 'GoodsBatchController@index');
    Route::any('goods_booking.php', 'GoodsBookingController@index');
    Route::any('goods_export.php', 'GoodsExportController@index');
    Route::any('goods_inventory_logs.php', 'GoodsInventoryLogsController@index');
    Route::any('goods_lib.php', 'GoodsLibController@index');
    Route::any('goods_lib_batch.php', 'GoodsLibBatchController@index');
    Route::any('goods_lib_cat.php', 'GoodsLibCatController@index');
    Route::any('goods_produts_area_batch.php', 'GoodsProdutsAreaBatchController@index');
    Route::any('goods_produts_batch.php', 'GoodsProdutsBatchController@index');
    Route::any('goods_produts_warehouse_batch.php', 'GoodsProdutsWarehouseBatchController@index');
    Route::any('goods_psi.php', 'GoodsPsiController@index');
    Route::any('goods_report.php', 'GoodsReportController@index');
    Route::any('goods_transport.php', 'GoodsTransportController@index');
    Route::any('goods_type.php', 'GoodsTypeController@index');
    Route::any('goods_warehouse_attr.php', 'GoodsWarehouseAttrController@index');
    Route::any('goods_warehouse_attr_batch.php', 'GoodsWarehouseAttrBatchController@index');
    Route::any('goods_warehouse_batch.php', 'GoodsWarehouseBatchController@index');
    Route::any('group_buy.php', 'GroupBuyController@index');
    Route::any('guest_stats.php', 'GuestStatsController@index');
    Route::any('help.php', 'HelpController@index');
    Route::any('index.php', 'IndexController@index')->name('admin.home');
    Route::any('integrate.php', 'IntegrateController@index');
    Route::any('keywords_manage.php', 'KeywordsManageController@index');
    Route::any('magazine_list.php', 'MagazineListController@index');
    Route::any('mail_template.php', 'MailTemplateController@index');
    Route::any('mass_sms.php', 'MassSmsController@index');
    Route::any('mc_order.php', 'McOrderController@index');
    Route::any('mc_user.php', 'McUserController@index');
    Route::any('merchants_account.php', 'MerchantsAccountController@index');
    Route::any('merchants_brand.php', 'MerchantsBrandController@index');
    Route::any('merchants_commission.php', 'MerchantsCommissionController@index');
    Route::any('merchants_custom.php', 'MerchantsCustomController@index');
    Route::any('merchants_navigator.php', 'MerchantsNavigatorController@index');
    Route::any('merchants_percent.php', 'MerchantsPercentController@index');
    Route::any('merchants_privilege.php', 'MerchantsPrivilegeController@index');
    Route::any('merchants_steps.php', 'MerchantsStepsController@index');
    Route::any('merchants_template.php', 'MerchantsTemplateController@index');
    Route::any('merchants_upgrade.php', 'MerchantsUpgradeController@index');
    Route::any('merchants_users_list.php', 'MerchantsUsersListController@index');
    Route::any('merchants_window.php', 'MerchantsWindowController@index');
    Route::any('message.php', 'MessageController@index');
    Route::any('navigator.php', 'NavigatorController@index');
    Route::any('notice_logs.php', 'NoticeLogsController@index');
    Route::any('offline_store.php', 'OfflineStoreController@index');
    Route::any('open_api.php', 'OpenApiController@index');
    Route::any('order.php', 'OrderController@index');
    Route::any('order_delay.php', 'OrderDelayController@index');
    Route::any('order_stats.php', 'OrderStatsController@index');
    Route::any('oss_configure.php', 'OssConfigureController@index');
    Route::any('obs_configure.php', 'ObsConfigureController@index');
    Route::any('cloud_setting.php', 'CloudSettingController@index');
    Route::any('pack.php', 'PackController@index');
    Route::any('package.php', 'PackageController@index');
    Route::any('pay_card.php', 'PayCardController@index');
    Route::any('payment.php', 'PaymentController@index');
    Route::any('picture_batch.php', 'PictureBatchController@index');
    Route::any('presale.php', 'PresaleController@index');
    Route::any('presale_cat.php', 'PresaleCatController@index');
    Route::any('print_batch.php', 'PrintBatchController@index');
    Route::any('privilege.php', 'PrivilegeController@index');
    Route::any('privilege_seller.php', 'PrivilegeSellerController@index');
    Route::any('reg_fields.php', 'RegFieldsController@index');
    Route::any('region.php', 'RegionController@index');
    Route::any('region_area.php', 'RegionAreaController@index');
    Route::any('region_store.php', 'RegionStoreController@index');
    Route::any('role.php', 'RoleController@index');
    Route::any('shop_stats.php', 'ShopStatsController@index');
    Route::any('user_stats.php', 'UserStatsController@index');
    Route::any('sell_analysis.php', 'SellAnalysisController@index');
    Route::any('industry_analysis.php', 'IndustryAnalysisController@index');
    Route::any('sale_general.php', 'SaleGeneralController@index');
    Route::any('sale_list.php', 'SaleListController@index');
    Route::any('sale_notice.php', 'SaleNoticeController@index');
    Route::any('sale_order.php', 'SaleOrderController@index');
    Route::any('search_log.php', 'SearchLogController@index');
    Route::any('searchengine_stats.php', 'SearchengineStatsController@index');
    Route::any('seckill.php', 'SeckillController@index');
    Route::any('seller_apply.php', 'SellerApplyController@index');
    Route::any('seller_domain.php', 'SellerDomainController@index');
    Route::any('seller_grade.php', 'SellerGradeController@index');
    Route::any('seller_shop_bg.php', 'SellerShopBgController@index');
    Route::any('seller_shop_slide.php', 'SellerShopSlideController@index');
    Route::any('seo.php', 'SeoController@index');
    Route::any('services.php', 'ServicesController@index');
    Route::any('set_floor_brand.php', 'SetFloorBrandController@index');
    Route::any('shipping.php', 'ShippingController@index');
    Route::any('shipping_area.php', 'ShippingAreaController@index');
    Route::any('shop_config.php', 'ShopConfigController@index');
    Route::any('shophelp.php', 'ShophelpController@index');
    Route::any('shopinfo.php', 'ShopinfoController@index');
    Route::any('sitemap.php', 'SitemapController@index');
    Route::any('snatch.php', 'SnatchController@index');
    Route::any('sql.php', 'SqlController@index');
    Route::any('table_prefix.php', 'TablePrefixController@index');
    Route::any('tag_manage.php', 'TagManageController@index');

    /**
     * 短信插件
     */
    Route::prefix('sms')->name('admin.sms.')->middleware('web', 'admin_priv:sms_setting')->group(function () {
        Route::get('/', 'SmsController@index')->name('index');
        Route::any('edit', 'SmsController@edit')->name('edit');
        Route::any('uninstall', 'SmsController@uninstall')->name('uninstall');
    });
    Route::any('sms_setting.php', 'SmsSettingController@index'); // 短信设置
    Route::any('dscsms_configure.php', 'DscsmsConfigureController@index'); // 短信模板

    /* 批发 start */
    Route::any('suppliers.php', 'SuppliersController@index');
    Route::any('suppliers_goods.php', 'SuppliersGoodsController@index');
    Route::any('suppliers_account.php', 'SuppliersAccountController@index');
    Route::any('suppliers_commission.php', 'SuppliersCommissionController@index');
    Route::any('suppliers_sale_list.php', 'SuppliersSaleListController@index');
    Route::any('suppliers_stats.php', 'SuppliersStatsController@index');

    Route::any('wholesale.php', 'WholesaleController@index');
    Route::any('wholesale_cat.php', 'WholesaleCatController@index');
    Route::any('wholesale_goods_produts_batch.php', 'WholesaleGoodsProdutsBatchController@index');
    Route::any('wholesale_order.php', 'WholesaleOrderController@index');
    Route::any('wholesale_purchase.php', 'WholesalePurchaseController@index');
    /* 批发 end */

    // 贡云 后台配置路由
    Route::middleware('web')->prefix('cloudapi')->group(function () {
        Route::any('/', 'CloudApiController@index')->name('cloudapi.index');
        Route::any('update', 'CloudApiController@update')->name('cloudapi.update');
    });

    // 拼团模块
    Route::prefix('team')->name('admin/team/')->middleware('web', 'admin_priv:team_manage')->group(function () {
        Route::any('/', 'TeamController@index')->name('index');
        Route::any('addgoods', 'TeamController@addgoods')->name('addgoods');
        Route::any('searchgoods', 'TeamController@searchgoods')->name('searchgoods');
        Route::any('filtercategory', 'TeamController@filtercategory')->name('filtercategory');
        Route::any('searchbrand', 'TeamController@searchbrand')->name('searchbrand');
        Route::any('editgoods', 'TeamController@editgoods')->name('editgoods');
        Route::any('removegoods', 'TeamController@removegoods')->name('removegoods');
        Route::any('category', 'TeamController@category')->name('category');
        Route::any('addcategory', 'TeamController@addcategory')->name('addcategory');
        Route::any('removecategory', 'TeamController@removecategory')->name('removecategory');
        Route::any('editstatus', 'TeamController@editstatus')->name('editstatus');
        Route::any('teaminfo', 'TeamController@teaminfo')->name('teaminfo');
        Route::any('teamorder', 'TeamController@teamorder')->name('teamorder');
        Route::any('removeteam', 'TeamController@removeteam')->name('removeteam');
        Route::any('teamrecycle', 'TeamController@teamrecycle')->name('teamrecycle');
        Route::any('recycleegoods', 'TeamController@recycleegoods')->name('recycleegoods');
    });

    Route::any('template.php', 'TemplateController@index');
    Route::any('template_mall.php', 'TemplateMallController@index');
    Route::any('topic.php', 'TopicController@index');
    // 手机端授权登录
    Route::prefix('touch_oauth')->name('admin/touch_oauth/')->middleware('web', 'admin_priv:oauth_admin')->group(function () {
        Route::any('/', 'TouchOauthController@index')->name('index');
        Route::any('edit', 'TouchOauthController@edit')->name('edit');
        Route::any('install', 'TouchOauthController@install')->name('install');
        Route::any('uninstall', 'TouchOauthController@uninstall')->name('uninstall');
    });

    // 手机端可视化
    Route::prefix('touch_visual')->name('admin/touch_visual/')->group(function () {
        Route::any('/', 'TouchVisualController@index')->name('index');
        Route::post('view', 'TouchVisualController@view')->name('view');
        Route::post('article', 'TouchVisualController@article')->name('article');
        Route::get('article_list', 'TouchVisualController@article_list')->name('article_list');
        Route::post('product', 'TouchVisualController@product')->name('product');
        Route::post('checked', 'TouchVisualController@checked')->name('checked');
        Route::post('category', 'TouchVisualController@category')->name('category');
        Route::post('brand', 'TouchVisualController@brand')->name('brand');
        Route::post('thumb', 'TouchVisualController@thumb')->name('thumb');
        Route::post('geturl', 'TouchVisualController@geturl')->name('geturl');
        Route::post('seckill', 'TouchVisualController@seckill')->name('seckill');
        Route::post('store', 'TouchVisualController@store')->name('store');
        Route::post('storeIn', 'TouchVisualController@storeIn')->name('storeIn');
        Route::post('storeDown', 'TouchVisualController@storeDown')->name('storeDown');
        Route::post('default_index', 'TouchVisualController@default_index')->name('default_index');
        Route::post('previewModule', 'TouchVisualController@previewModule')->name('previewModule');
        Route::post('saveModule', 'TouchVisualController@saveModule')->name('saveModule');
        Route::post('cleanModule', 'TouchVisualController@cleanModule')->name('cleanModule');
        Route::post('restore', 'TouchVisualController@restore')->name('restore');
        Route::post('clean', 'TouchVisualController@clean')->name('clean');
        Route::post('save', 'TouchVisualController@save')->name('save');
        Route::post('del', 'TouchVisualController@del')->name('del');
        Route::post('make_gallery', 'TouchVisualController@make_gallery')->name('make_gallery');
        Route::post('picture', 'TouchVisualController@picture')->name('picture');
        Route::post('remove_picture', 'TouchVisualController@remove_picture')->name('remove_picture');
        Route::post('pic_upload', 'TouchVisualController@pic_upload')->name('pic_upload');
        Route::post('title', 'TouchVisualController@title')->name('title');
        Route::post('search', 'TouchVisualController@search')->name('search');
    });

    // 消息提示
    Route::any('base/message', 'BaseController@message')->name('admin/base/message');

    // 微信通后台模块
    Route::prefix('wechat')->name('admin/wechat/')->group(function () {
        Route::any('/', 'WechatController@index')->name('index')->middleware('admin_priv:wechat_admin');
        Route::any('modify', 'WechatController@modify')->name('modify')->middleware('admin_priv:wechat_admin');
        Route::any('menu_list', 'WechatController@menu_list')->name('menu_list')->middleware('admin_priv:menu');
        Route::any('menu_edit', 'WechatController@menu_edit')->name('menu_edit')->middleware('admin_priv:menu');
        Route::any('menu_del', 'WechatController@menu_del')->name('menu_del')->middleware('admin_priv:menu');
        Route::any('sys_menu', 'WechatController@sys_menu')->name('sys_menu')->middleware('admin_priv:menu');
        Route::any('subscribe_list', 'WechatController@subscribe_list')->name('subscribe_list')->middleware('admin_priv:fans');
        Route::any('subscribe_search', 'WechatController@subscribe_search')->name('subscribe_search')->middleware('admin_priv:fans');
        Route::any('sysfans', 'WechatController@sysfans')->name('sysfans')->middleware('admin_priv:fans');
        Route::any('subscribe_update', 'WechatController@subscribe_update')->name('subscribe_update')->middleware('admin_priv:fans');
        Route::any('send_custom_message', 'WechatController@send_custom_message')->name('send_custom_message')->middleware('admin_priv:fans');
        Route::any('select_custom_message', 'WechatController@select_custom_message')->name('select_custom_message')->middleware('admin_priv:fans');
        Route::any('custom_message_list', 'WechatController@custom_message_list')->name('custom_message_list')->middleware('admin_priv:fans');
        Route::any('tags_list', 'WechatController@tags_list')->name('tags_list')->middleware('admin_priv:fans');
        Route::any('sys_tags', 'WechatController@sys_tags')->name('sys_tags')->middleware('admin_priv:fans');
        Route::any('user_tag_update', 'WechatController@user_tag_update')->name('user_tag_update')->middleware('admin_priv:fans');
        Route::any('tags_edit', 'WechatController@tags_edit')->name('tags_edit')->middleware('admin_priv:fans');
        Route::any('tags_delete', 'WechatController@tags_delete')->name('tags_delete')->middleware('admin_priv:fans');
        Route::any('batch_tagging', 'WechatController@batch_tagging')->name('batch_tagging')->middleware('admin_priv:fans');
        Route::any('batch_untagging', 'WechatController@batch_untagging')->name('batch_untagging')->middleware('admin_priv:fans');
        Route::any('edit_user_remark', 'WechatController@edit_user_remark')->name('edit_user_remark')->middleware('admin_priv:fans');
        Route::any('qrcode_list', 'WechatController@qrcode_list')->name('qrcode_list')->middleware('admin_priv:qrcode');
        Route::any('qrcode_edit', 'WechatController@qrcode_edit')->name('qrcode_edit')->middleware('admin_priv:qrcode');
        Route::any('share_list', 'WechatController@share_list')->name('share_list')->middleware('admin_priv:share');
        Route::any('share_edit', 'WechatController@share_edit')->name('share_edit')->middleware('admin_priv:share');
        Route::any('get_user_id', 'WechatController@get_user_id')->name('get_user_id')->middleware('admin_priv:share');
        Route::any('qrcode_del', 'WechatController@qrcode_del')->name('qrcode_del')->middleware('admin_priv:qrcode');
        Route::any('qrcode_get', 'WechatController@qrcode_get')->name('qrcode_get')->middleware('admin_priv:qrcode');
        Route::any('article', 'WechatController@article')->name('article')->middleware('admin_priv:media');
        Route::any('article_edit', 'WechatController@article_edit')->name('article_edit')->middleware('admin_priv:media');
        Route::any('gallery_album', 'WechatController@gallery_album')->name('gallery_album')->middleware('admin_priv:media');
        Route::any('article_edit_news', 'WechatController@article_edit_news')->name('article_edit_news')->middleware('admin_priv:media');
        Route::any('articles_list', 'WechatController@articles_list')->name('articles_list')->middleware('admin_priv:media');
        Route::any('get_article', 'WechatController@get_article')->name('get_article')->middleware('admin_priv:media');
        Route::any('article_news_del', 'WechatController@article_news_del')->name('article_news_del')->middleware('admin_priv:media');
        Route::any('article_del', 'WechatController@article_del')->name('article_del')->middleware('admin_priv:media');
        Route::any('picture', 'WechatController@picture')->name('picture')->middleware('admin_priv:media');
        Route::any('voice', 'WechatController@voice')->name('voice')->middleware('admin_priv:media');
        Route::any('video', 'WechatController@video')->name('video')->middleware('admin_priv:media');
        Route::any('video_edit', 'WechatController@video_edit')->name('video_edit')->middleware('admin_priv:media');
        Route::any('video_upload', 'WechatController@video_upload')->name('video_upload')->middleware('admin_priv:media');
        Route::any('media_edit', 'WechatController@media_edit')->name('media_edit')->middleware('admin_priv:media');
        Route::any('media_del', 'WechatController@media_del')->name('media_del')->middleware('admin_priv:media');
        Route::any('download', 'WechatController@download')->name('download')->middleware('admin_priv:media');
        Route::any('mass_list', 'WechatController@mass_list')->name('mass_list')->middleware('admin_priv:mass_message');
        Route::any('mass_message', 'WechatController@mass_message')->name('mass_message')->middleware('admin_priv:mass_message');
        Route::any('mass_del', 'WechatController@mass_del')->name('mass_del')->middleware('admin_priv:mass_message');
        Route::any('auto_reply', 'WechatController@auto_reply')->name('auto_reply')->middleware('admin_priv:auto_reply');
        Route::any('reply_subscribe', 'WechatController@reply_subscribe')->name('reply_subscribe')->middleware('admin_priv:auto_reply');
        Route::any('reply_msg', 'WechatController@reply_msg')->name('reply_msg')->middleware('admin_priv:auto_reply');
        Route::any('reply_keywords', 'WechatController@reply_keywords')->name('reply_keywords')->middleware('admin_priv:auto_reply');
        Route::any('rule_edit', 'WechatController@rule_edit')->name('rule_edit')->middleware('admin_priv:auto_reply');
        Route::any('reply_del', 'WechatController@reply_del')->name('reply_del')->middleware('admin_priv:auto_reply');
        Route::any('extend_index', 'WechatController@extend_index')->name('extend_index')->middleware('admin_priv:extend');
        Route::any('extend_edit', 'WechatController@extend_edit')->name('extend_edit')->middleware('admin_priv:extend');
        Route::any('extend_uninstall', 'WechatController@extend_uninstall')->name('extend_uninstall')->middleware('admin_priv:extend');
        Route::any('winner_list', 'WechatController@winner_list')->name('winner_list')->middleware('admin_priv:extend');
        Route::any('export_winner', 'WechatController@export_winner')->name('export_winner')->middleware('admin_priv:extend');
        Route::any('winner_issue', 'WechatController@winner_issue')->name('winner_issue')->middleware('admin_priv:extend');
        Route::any('winner_del', 'WechatController@winner_del')->name('winner_del')->middleware('admin_priv:extend');

        Route::any('sign_list', 'WechatController@sign_list')->name('sign_list')->middleware('admin_priv:extend');
        Route::any('export_sign', 'WechatController@export_sign')->name('export_sign')->middleware('admin_priv:extend');
        Route::any('sign_del', 'WechatController@sign_del')->name('sign_del')->middleware('admin_priv:extend');

        Route::any('market_index', 'WechatController@market_index')->name('market_index')->middleware('admin_priv:market');
        Route::any('market_list', 'WechatController@market_list')->name('market_list')->middleware('admin_priv:market');
        Route::any('market_edit', 'WechatController@market_edit')->name('market_edit')->middleware('admin_priv:market');
        Route::any('market_del', 'WechatController@market_del')->name('market_del')->middleware('admin_priv:market');
        Route::any('data_list', 'WechatController@data_list')->name('data_list')->middleware('admin_priv:market');
        Route::any('market_qrcode', 'WechatController@market_qrcode')->name('market_qrcode')->middleware('admin_priv:market');
        Route::any('market_action', 'WechatController@market_action')->name('market_action')->middleware('admin_priv:market');

        Route::any('template', 'WechatController@template')->name('template')->middleware('admin_priv:template');
        Route::any('edit_template', 'WechatController@edit_template')->name('edit_template')->middleware('admin_priv:template');
        Route::any('switch_template', 'WechatController@switch_template')->name('switch_template')->middleware('admin_priv:template');
        Route::any('reset_template', 'WechatController@reset_template')->name('reset_template')->middleware('admin_priv:template');
        Route::any('share_count', 'WechatController@share_count')->name('share_count')->middleware('admin_priv:fans');
        Route::any('share_count_delete', 'WechatController@share_count_delete')->name('share_count_delete')->middleware('admin_priv:fans');
    });

    // 微分销后台模块
    Route::prefix('drp')->name('admin/drp/')->group(function () {
        Route::any('index', 'DrpController@index')->name('index')->middleware('admin_priv:drp_config');
        Route::any('config', 'DrpController@config')->name('config')->middleware('admin_priv:drp_config');
        Route::any('drp_scale_config', 'DrpController@drp_scale_config')->name('drp_scale_config')->middleware('admin_priv:drp_config');
        Route::any('drp_set_qrcode', 'DrpController@drp_set_qrcode')->name('drp_set_qrcode')->middleware('admin_priv:drp_config');
        Route::any('reset_qrconfig', 'DrpController@reset_qrconfig')->name('reset_qrconfig')->middleware('admin_priv:drp_config');
        Route::any('delete_user_qrcode', 'DrpController@delete_user_qrcode')->name('delete_user_qrcode')->middleware('admin_priv:drp_config');
        Route::any('remove_bg', 'DrpController@remove_bg')->name('remove_bg')->middleware('admin_priv:drp_config');
        Route::any('synchro_images', 'DrpController@synchro_images')->name('synchro_images')->middleware('admin_priv:drp_config');
        Route::any('shop', 'DrpController@shop')->name('shop')->middleware('admin_priv:drp_shop');
        Route::any('drp_aff_list', 'DrpController@drp_aff_list')->name('drp_aff_list')->middleware('admin_priv:drp_shop');
        Route::any('add_shop', 'DrpController@add_shop')->name('add_shop')->middleware('admin_priv:drp_shop');
        Route::any('edit_shop', 'DrpController@edit_shop')->name('edit_shop')->middleware('admin_priv:drp_shop');
        Route::any('set_shop', 'DrpController@set_shop')->name('set_shop')->middleware('admin_priv:drp_shop');
        Route::any('export_shop', 'DrpController@export_shop')->name('export_shop')->middleware('admin_priv:drp_shop');
        Route::any('drp_list', 'DrpController@drp_list')->name('drp_list')->middleware('admin_priv:drp_list');
        Route::any('drp_order_list', 'DrpController@drp_order_list')->name('drp_order_list')->middleware('admin_priv:drp_order_list');
        Route::any('separate_drp_order', 'DrpController@separate_drp_order')->name('separate_drp_order')->middleware('admin_priv:drp_order_list');
        Route::any('del_drp_order', 'DrpController@del_drp_order')->name('del_drp_order');
        Route::any('drp_order_list_buy', 'DrpController@drp_order_list_buy')->name('drp_order_list_buy')->middleware('admin_priv:drp_order_list');
        Route::any('export_buy', 'DrpController@export_buy')->name('export_buy')->middleware('admin_priv:drp_order_list');
        Route::any('separate_drp_order_buy', 'DrpController@separate_drp_order_buy')->name('separate_drp_order_buy')->middleware('admin_priv:drp_order_list');
        Route::any('del_drp_order_buy', 'DrpController@del_drp_order_buy')->name('del_drp_order_buy')->middleware('admin_priv:drp_order_list');
        Route::any('rollback_drp_order', 'DrpController@rollback_drp_order')->name('rollback_drp_order')->middleware('admin_priv:drp_order_list');
        Route::any('drp_count', 'DrpController@drp_count')->name('drp_count')->middleware('admin_priv:drp_count');
        Route::any('export_order', 'DrpController@export_order')->name('export_order')->middleware('admin_priv:drp_order_list');
    });
    // 分销权益卡
    Route::prefix('drp_card')->name('admin/drp_card/')->middleware('web', 'admin_priv:drpcard_manage')->group(function () {
        Route::any('/', 'DrpCardController@index')->name('index');
        Route::any('index', 'DrpCardController@index')->name('index');
        Route::any('add', 'DrpCardController@add')->name('add');
        Route::any('edit', 'DrpCardController@edit')->name('edit');
        Route::any('delete', 'DrpCardController@delete')->name('delete');
        Route::any('select_goods', 'DrpCardController@select_goods')->name('select_goods');
        Route::any('bind_rights', 'DrpCardController@bind_rights')->name('bind_rights');
        Route::any('edit_rights', 'DrpCardController@edit_rights')->name('edit_rights');
        Route::any('unbind_rights', 'DrpCardController@unbind_rights')->name('unbind_rights');
        Route::any('sync', 'DrpCardController@sync')->name('sync');
        Route::any('remove_img', 'DrpCardController@remove_img')->name('remove_img');
        Route::any('get_goods', 'DrpCardController@get_goods')->name('get_goods');
        Route::post('disabled', 'DrpCardController@disabled')->name('disabled');
    });

    // 微信小程序
    Route::prefix('wxapp')->name('admin/wxapp/')->middleware('web', 'admin_priv:wxapp_wechat_config')->group(function () {
        Route::any('index', 'WxappController@index')->name('index');
        Route::any('delete', 'WxappController@delete')->name('delete');
        Route::any('template', 'WxappController@template')->name('template');
        Route::any('edit_template', 'WxappController@edit_template')->name('edit_template');
        Route::any('switch_template', 'WxappController@switch_template')->name('switch_template');
        Route::any('reset_template', 'WxappController@reset_template')->name('reset_template');
    });

    Route::any('touch_ad_position.php', 'TouchAdPositionController@index');
    Route::any('touch_ads.php', 'TouchAdsController@index');
    Route::any('touch_topic.php', 'TouchTopicController@index');
    Route::any('tp_api.php', 'TpApiController@index');
    Route::any('transfer_manage.php', 'TransferManageController@index');
    Route::any('user_account.php', 'UserAccountController@index');
    Route::any('user_account_manage.php', 'UserAccountManageController@index');
    Route::any('user_address_log.php', 'UserAddressLogController@index');
    Route::any('user_baitiao_log.php', 'UserBaitiaoLogController@index');
    Route::any('user_msg.php', 'UserMsgController@index');
    Route::any('user_rank.php', 'UserRankController@index');
    Route::any('user_real.php', 'UserRealController@index');
    Route::any('user_vat.php', 'UserVatController@index');
    Route::any('users.php', 'UsersController@index');
    Route::any('users_order.php', 'UsersOrderController@index');
    Route::any('value_card.php', 'ValueCardController@index');
    Route::any('view_sendlist.php', 'ViewSendlistController@index');
    Route::any('virtual_card.php', 'VirtualCardController@index');
    Route::any('visit_sold.php', 'VisitSoldController@index');
    Route::any('visual_editing.php', 'VisualEditingController@index');
    Route::any('visualhome.php', 'VisualhomeController@index');
    Route::any('visualnews.php', 'VisualnewsController@index');
    Route::any('vote.php', 'VoteController@index');
    Route::any('warehouse.php', 'WarehouseController@index');
    Route::any('warehouse_order.php', 'WarehouseOrderController@index');
    Route::any('warehouse_shipping_mode.php', 'WarehouseShippingModeController@index');
    Route::any('website.php', 'WebsiteController@index');
    Route::any('zc_category.php', 'ZcCategoryController@index');
    Route::any('zc_initiator.php', 'ZcInitiatorController@index');
    Route::any('zc_project.php', 'ZcProjectController@index');
    Route::any('zc_topic.php', 'ZcTopicController@index');
    Route::any('editor.php', 'EditorController@index');
    // Route::any('upgrade.php', 'UpgradeController@index')->name('admin.upgrade');

    // APP管理
    Route::prefix('app')->name('admin/app/')->middleware('web')->group(function () {
        // APP配置
        Route::any('index', 'AppController@index')->name('index');
        // 广告位列表
        Route::any('ad_position_list', 'AppController@ad_position_list')->name('ad_position_list');
        // 添加或更新广告位
        Route::post('update_position', 'AppController@update_position')->name('update_position');
        // 广告位信息
        Route::get('ad_position_info', 'AppController@ad_position_info')->name('ad_position_info');
        // 删除广告位
        Route::post('delete_position', 'AppController@delete_position')->name('delete_position');
        // 广告列表
        Route::any('ads_list', 'AppController@ads_list')->name('ads_list');
        // 添加或更新广告
        Route::post('update_ads', 'AppController@update_ads')->name('update_ads');
        // 广告信息
        Route::get('ads_info', 'AppController@ads_info')->name('ads_info');
        // 修改广告状态
        Route::get('change_ad_status', 'AppController@change_ad_status')->name('change_ad_status');
        // 删除广告
        Route::post('delete_ad', 'AppController@delete_ad')->name('delete_ad');
    });

    // 过滤词
    Route::prefix('filter')->name('admin/filter/')->group(function () {
        // 配置
        Route::any('/', 'FilterWordsController@index')->name('index');
        Route::any('update', 'FilterWordsController@update')->name('update');

        // 关键词
        Route::any('words', 'FilterWordsController@words')->name('words');
        Route::any('wordsupdate/{id?}', 'FilterWordsController@wordsupdate')->name('wordsupdate');
        Route::any('wordsdrop', 'FilterWordsController@wordsdrop')->name('wordsdrop');
        Route::any('batchdrop', 'FilterWordsController@batchdrop')->name('batchdrop');
        Route::any('pages', 'FilterWordsController@pages')->name('pages');
        Route::any('batch', 'FilterWordsController@batch')->name('batch');
        Route::any('batchlist', 'FilterWordsController@batchlist')->name('batchlist');
        Route::any('download', 'FilterWordsController@download')->name('download');
        Route::any('error', 'FilterWordsController@error')->name('error');

        // 等级
        Route::any('ranks', 'FilterWordsController@ranks')->name('ranks');
        Route::any('ranksupdate/{id?}', 'FilterWordsController@ranksupdate')->name('ranksupdate');

        // 记录
        Route::any('logs', 'FilterWordsController@logs')->name('logs');
        Route::any('updatelogs', 'FilterWordsController@updatelogs')->name('updatelogs');
        Route::any('logsdrop', 'FilterWordsController@logsdrop')->name('logsdrop');
        // 统计
        Route::any('stats', 'FilterWordsController@stats')->name('stats');
    });

    // 会员权益管理
    Route::prefix('user_rights')->name('admin/user_rights/')->middleware('web', 'admin_priv:user_rights')->group(function () {
        Route::any('/', 'UserRightsController@index')->name('index');
        Route::any('list', 'UserRightsController@list')->name('list');
        Route::any('edit', 'UserRightsController@edit')->name('edit');
        Route::any('uninstall', 'UserRightsController@uninstall')->name('uninstall');
    });

    // 会员等级
    Route::prefix('user_rank')->name('admin/user_rank/')->group(function () {
        Route::any('/', 'UserRankController@index')->name('index');
        Route::any('index', 'UserRankController@index')->name('index');
        Route::any('edit', 'UserRankNewController@edit')->name('edit');
        Route::any('delete', 'UserRankNewController@delete')->name('delete');
        Route::any('bind_rights', 'UserRankNewController@bind_rights')->name('bind_rights');
        Route::any('edit_rights', 'UserRankNewController@edit_rights')->name('edit_rights');
        Route::any('unbind_rights', 'UserRankNewController@unbind_rights')->name('unbind_rights');
    });
});
