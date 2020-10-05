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

Route::any('/', 'IndexController@index')->name('home');
Route::any('index.html', 'IndexController@index');

Route::any('activity.php', 'ActivityController@index')->name('activity');
Route::any('activity.html', 'ActivityController@index');

Route::any('affiche.php', 'AfficheController@index')->name('affiche');
Route::any('affiliate.php', 'AffiliateController@index')->name('affiliate');
Route::any('ajax_dialog.php', 'AjaxDialogController@index')->name('ajax_dialog');
Route::any('ajax_user.php', 'AjaxUserController@index')->name('ajax_user');
Route::any('ajax_category.php', 'AjaxCategoryController@index')->name('ajax_category');
Route::any('ajax_goods.php', 'AjaxGoodsController@index')->name('ajax_goods');
Route::any('ajax_article.php', 'AjaxArticleController@index')->name('ajax_article');
Route::any('ajax_shop.php', 'AjaxShopController@index')->name('ajax_shop');
Route::any('ajax_wholesale.php', 'AjaxWholesaleController@index')->name('ajax_wholesale');
Route::any('ajax_flow.php', 'AjaxFlowController@index')->name('ajax_flow');
Route::any('ajax_flow_address.php', 'AjaxFlowAddressController@index')->name('ajax_flow_address');
Route::any('ajax_flow_activity.php', 'AjaxFlowActivityController@index')->name('ajax_flow_activity');
Route::any('ajax_flow_goods.php', 'AjaxFlowGoodsController@index')->name('ajax_flow_goods');
Route::any('ajax_flow_user.php', 'AjaxFlowUserController@index')->name('ajax_flow_user');
Route::any('ajax_flow_pay.php', 'AjaxFlowPayController@index')->name('ajax_flow_pay');
Route::any('ajax_flow_shipping.php', 'AjaxFlowShippingController@index')->name('ajax_flow_shipping');
Route::any('ajax_cart.php', 'AjaxCartController@index')->name('ajax_cart');
Route::any('api.php', 'ApiController@index')->name('api');

Route::any('article.php', 'ArticleController@index')->name('article');
Route::any('article-{id}.html', 'ArticleController@index')
    ->where('id', '[0-9]+');

Route::any('article_cat.php', 'ArticleCatController@index')->name('article_cat');
Route::any('article_cat-{id}-{page}-{sort}-{order}.html', 'ArticleCatController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('article_cat-{id}-{page}-{keywords}.html', 'ArticleCatController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'keywords' => '.+']);
Route::any('article_cat-{id}-{page}.html', 'ArticleCatController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+']);
Route::any('article_cat-{id}.html', 'ArticleCatController@index')
    ->where('id', '[0-9]+');

Route::any('auction.php', 'AuctionController@index')->name('auction');
Route::any('auction.html', 'AuctionController@index');
Route::any('auction-{id}.html', 'AuctionController@index')
    ->defaults('act', 'view')
    ->where('id', '[0-9]+');

Route::any('bonus.php', 'BonusController@index')->name('bonus');
Route::any('bouns_available.php', 'BounsAvailableController@index')->name('bouns_available');
Route::any('bouns_expire.php', 'BounsExpireController@index')->name('bouns_expire');
Route::any('bouns_useup.php', 'BounsUseupController@index')->name('bouns_useup');
// 品牌
Route::any('brand.php', 'BrandController@index')->name('brand');
Route::any('brand.html', 'BrandController@index');
Route::any('brand-{id}-c{cat}-min{price_min}-max{price_max}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-min{price_min}-max{price_max}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-min{price_min}-max{price_max}-ship{ship}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-ship{ship}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-min{price_min}-max{price_max}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-min{price_min}-max{price_max}-{page}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'page' => '[0-9]+']);
Route::any('brand-{id}-c{cat}-min{price_min}-max{price_max}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+']);
Route::any('brand-{id}-mbid{mbid}-min{price_min}-max{price_max}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-min{price_min}-max{price_max}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-min{price_min}-max{price_max}-ship{ship}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-min{price_min}-max{price_max}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-self{self}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-ship{ship}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-c{cat}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-{page}-{sort}-{order}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('brand-{id}-mbid{mbid}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'mbid' => '[0-9]+']);
Route::any('brand-{id}-c{cat}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+']);
Route::any('brand-{id}.html', 'BrandController@index')
    ->where(['id' => '[0-9]+']);

Route::any('brandn.php', 'BrandnController@index')->name('brandn');
Route::any('brandn-{id}-c{cat}-{page}-{sort}-{order}-{act}.html', 'BrandnController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('brandn-{id}-{page}-{sort}-{order}-{act}.html', 'BrandnController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('brandn-{id}-c{cat}-{page}-{act}.html', 'BrandnController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'page' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('brandn-{id}-c{cat}-{act}.html', 'BrandnController@index')
    ->where(['id' => '[0-9]+', 'cat' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('brandn-{id}-{cat}.html', 'BrandnController@index')
    ->where(['id' => '[0-9]+', 'cat' => '([a-zA-Z])+([^-]*)']);
Route::any('brandn-{id}.html', 'BrandnController@index')
    ->where(['id' => '[0-9]+']);
// 验证码
Route::any('captcha_verify.php', 'CaptchaVerifyController@index')->name('captcha_verify');
// 商品分类
Route::any('catalog.php', 'CatalogController@index')->name('catalog');
Route::any('category.php', 'CategoryController@index')->name('category');
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-attr{filter_attr}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}-self{self}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}-ship{ship}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-attr{filter_attr}-self{self}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-attr{filter_attr}-ship{ship}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-ship{ship}-self{self}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'ship' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-self{self}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'self' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-ship{ship}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'ship' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*']);
Route::any('category-{id}-min{price_min}-max{price_max}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-min{price_min}-max{price_max}-attr{filter_attr}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*']);
Route::any('category-{id}-b{brand}-min{price_min}-max{price_max}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+']);
Route::any('category-{id}-b{brand}-{page}-{sort}-{order}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('category-{id}-b{brand}-attr{filter_attr}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'filter_attr' => '[^-]*']);
Route::any('category-{id}-b{brand}-max{price_max}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'price_max' => '[0-9]+']);
Route::any('category-{id}-attr{filter_attr}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'filter_attr' => '[^-]*']);
Route::any('category-{id}-min{price_min}-max{price_max}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+']);
Route::any('category-{id}-max{price_max}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'price_max' => '[0-9]+']);
Route::any('category-{id}-b{brand}-{page}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*', 'page' => '[0-9]+']);
Route::any('category-{id}-b{brand}.html', 'CategoryController@index')
    ->where(['id' => '[0-9]+', 'brand' => '[^-]*']);
Route::any('category-{id}.html', 'CategoryController@index')
    ->where('id', '[0-9]+');

Route::any('category_compare.php', 'CategoryCompareController@index')->name('category_compare');
Route::any('category_discuss.php', 'CategoryDiscussController@index')->name('category_discuss');

Route::any('categoryall.php', 'CategoryallController@index')->name('categoryall');
Route::any('categoryall.html', 'CategoryallController@index');
Route::any('categoryall-{id}.html', 'CategoryallController@index')
    ->where('id', '[0-9]+');

Route::any('collection_brands.php', 'CollectionBrandsController@index')->name('collection_brands');
Route::any('collection_goods.php', 'CollectionGoodsController@index')->name('collection_goods');
Route::any('collection_store.php', 'CollectionStoreController@index')->name('collection_store');
Route::any('comment.php', 'CommentController@index')->name('comment');
Route::any('comment_discuss.php', 'CommentDiscussController@index')->name('comment_discuss');
Route::any('comment_repay.php', 'CommentRepayController@index')->name('comment_repay');
Route::any('comment_reply.php', 'CommentReplyController@index')->name('comment_reply');
Route::any('comment_reply_single.php', 'CommentReplySingleController@index')->name('comment_reply_single');
Route::any('comment_single.php', 'CommentSingleController@index')->name('comment_single');
Route::any('config.php', 'ConfigController@index')->name('config');
Route::any('config.rds.php', 'Config.rdsController@index')->name('config.rds');
Route::any('coudan.php', 'CoudanController@index')->name('coudan');
Route::any('coupons.php', 'CouponsController@index')->name('coupons');
Route::any('crowdfunding.php', 'CrowdfundingController@index')->name('crowdfunding');
Route::any('cycle_image.php', 'CycleImageController@index')->name('cycle_image');
Route::any('delete_cart_goods.php', 'DeleteCartGoodsController@index')->name('delete_cart_goods');
Route::any('delete_wholesale_cart_goods.php', 'DeleteWholesaleCartGoodsController@index')->name('delete_wholesale_cart_goods');

Route::any('exchange.php', 'ExchangeController@index')->name('exchange');
Route::any('exchange.html', 'ExchangeController@index');
Route::any('exchange-id{id}.html', 'ExchangeController@index')
    ->defaults('act', 'view')
    ->where(['id' => '[0-9]+']);
Route::any('exchange-{cat_id}-min{integral_min}-max{integral_max}-{page}-{sort}-{order}.html', 'ExchangeController@index')
    ->where(['cat_id' => '[0-9]+', 'integral_min' => '[0-9]+', 'integral_max' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('exchange-{cat_id}-{page}-{sort}-{order}.html', 'ExchangeController@index')
    ->where(['cat_id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('exchange-{cat_id}-{page}.html', 'ExchangeController@index')
    ->where(['cat_id' => '[0-9]+', 'page' => '[0-9]+']);
Route::any('exchange-{cat_id}.html', 'ExchangeController@index')
    ->where(['cat_id' => '[0-9]+']);
// Feed
Route::any('feed.php', 'FeedController@index')->name('feed');
Route::any('feed.xml', 'FeedController@index');
Route::any('feed-c{cat}.html', 'FeedController@index')
    ->where('cat', '[0-9]+');
Route::any('feed-b{brand}.html', 'FeedController@index')
    ->where('brand', '[0-9]+');
Route::any('feed-type{type}.html', 'FeedController@index')
    ->where('type', '[^-]+');

Route::any('flow.php', 'FlowController@index')->name('flow');
Route::any('flow.html', 'FlowController@index')->name('flow');
Route::any('flow_consignee.php', 'FlowConsigneeController@index')->name('flow_consignee');
Route::any('gallery.php', 'GalleryController@index')->name('gallery');
Route::any('get_ajax_content.php', 'GetAjaxContentController@index')->name('get_ajax_content');

Route::any('gift_gard.php', 'GiftGardController@index')->name('gift_gard');
Route::any('gift_gard.html', 'GiftGardController@index');
Route::any('gift_gard-{id}-{page}-{sort}-{order}.html', 'GiftGardController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('gift_gard-{id}-{page}.html', 'GiftGardController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+']);
Route::any('gift_gard-{id}.html', 'GiftGardController@index')
    ->where(['id' => '[0-9]+']);

Route::any('goods.php', 'GoodsController@index')->name('goods');
Route::any('goods-{id}.html', 'GoodsController@index')
    ->where('id', '[0-9]+');
Route::any('goods_script.php', 'GoodsScriptController@index')->name('goods_script');

Route::any('group_buy.php', 'GroupBuyController@index')->name('group_buy');
Route::any('group_buy.html', 'GroupBuyController@index');
Route::any('group_buy-{id}.html', 'GroupBuyController@index')
    ->defaults('act', 'view')
    ->where(['id' => '[0-9]+']);

Route::any('help.php', 'HelpController@index')->name('help');
// 浏览历史
Route::any('history_list.php', 'HistoryListController@index')->name('history_list');
Route::any('history_list.html', 'HistoryListController@index');
Route::any('history_list-{cat_id}-{page}.html', 'HistoryListController@index');

Route::any('homeindex.php', 'HomeindexController@index')->name('homeindex');
Route::any('index.php', 'IndexController@index')->name('index');
// 商家入驻
Route::any('merchants.php', 'MerchantsController@index')->name('merchants');
Route::any('merchants.html', 'MerchantsController@index');
Route::any('merchants-{id}.html', 'MerchantsController@index')
    ->where('id', '[0-9]+');
// 商家入驻流程
Route::any('merchants_steps.php', 'MerchantsStepsController@index')->name('merchants_steps');
Route::any('merchants_steps.html', 'MerchantsStepsController@index');
Route::any('merchants_steps_action.php', 'MerchantsStepsActionController@index')->name('merchants_steps_action');
Route::any('merchants_steps_site.php', 'MerchantsStepsSiteController@index')->name('merchants_steps_site');
Route::any('merchants_steps_site.html', 'MerchantsStepsSiteController@index');

Route::any('merchants_store.php', 'MerchantsStoreController@index')->name('merchants_store');
Route::any('merchants_store.html', 'MerchantsStoreController@index');
Route::any('merchants_store-{merchant_id}-c{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'brand' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store-{merchant_id}-c{id}-keyword{keyword}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'keyword' => '.+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store-{merchant_id}-c{id}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store-{merchant_id}-keyword{keyword}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'keyword' => '.+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store-{merchant_id}-min{price_min}-max{price_max}-attr{filter_attr}-{page}-{sort}-{order}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store-{merchant_id}-c{id}-b{brand}-min{price_min}-max{price_max}-attr{filter_attr}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'brand' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*']);
Route::any('merchants_store-{merchant_id}-c{id}-b{brand}-{page}-{sort}-{order}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'brand' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store-{merchant_id}-c{id}-min{price_min}-max{price_max}-attr{filter_attr}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'price_min' => '[0-9]+', 'price_max' => '[0-9]+', 'filter_attr' => '[^-]*']);
Route::any('merchants_store-{merchant_id}-c{id}-b{brand}-{page}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'brand' => '[0-9]+', 'page' => '[0-9]+']);
Route::any('merchants_store-{merchant_id}-c{id}-b{brand}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+', 'brand' => '[0-9]+']);
Route::any('merchants_store-{merchant_id}-c{id}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+', 'id' => '[0-9]+']);
Route::any('merchants_store-{merchant_id}.html', 'MerchantsStoreController@index')
    ->where(['merchant_id' => '[0-9]+']);
Route::any('merchants_store_shop.php', 'MerchantsStoreShopController@index')->name('merchants_store_shop');
Route::any('merchants_store_shop-{id}-{page}-{sort}-{order}.html', 'MerchantsStoreShopController@index')
    ->where(['id' => '[0-9]+', 'page' => '[0-9]+', 'sort' => '.+', 'order' => '[a-zA-Z]+']);
Route::any('merchants_store_shop-{id}.html', 'MerchantsStoreShopController@index')
    ->where(['id' => '[0-9]+']);
// 在线留言
Route::any('message.php', 'MessageController@index')->name('message');
Route::any('message.html', 'MessageController@index');

Route::any('myship.php', 'MyshipController@index')->name('myship');
Route::any('new_url.php', 'NewUrlController@index')->name('new_url');
Route::any('news.php', 'NewsController@index')->name('news');
// 授权登录
Route::any('oauth', 'OauthController@index')->name('oauth');
Route::any('oauth/bind_register', 'OauthController@bind_register')->name('oauth.bind_register');

Route::any('online.php', 'OnlineController@index')->name('online');
Route::any('oss.php', 'OssController@index')->name('oss');
Route::any('obs.php', 'ObsController@index')->name('obs');

Route::any('package.php', 'PackageController@index')->name('package');
Route::any('package.html', 'PackageController@index');

Route::any('pm.php', 'PmController@index')->name('pm');
// 预售
Route::any('presale.php', 'PresaleController@index')->name('presale');
Route::any('presale.html', 'PresaleController@index');
Route::any('presale-c{cat_id}-status{status}-{act}.html', 'PresaleController@index')
    ->where(['cat_id' => '[0-9]+', 'status' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('presale-status{status}-{act}.html', 'PresaleController@index')
    ->where(['status' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('presale-c{cat_id}-{act}.html', 'PresaleController@index')
    ->where(['cat_id' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('presale-{id}-{act}.html', 'PresaleController@index')
    ->where(['id' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('presale-{act}.html', 'PresaleController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('preview.php', 'PreviewController@index')->name('preview');
Route::any('quotation.php', 'QuotationController@index')->name('quotation');
Route::any('receive.php', 'ReceiveController@index')->name('receive');
Route::any('region.php', 'RegionController@index')->name('region');
Route::any('region_goods.php', 'RegionGoodsController@index')->name('region_goods');

// 计划任务
Route::any('cron.php', 'CronController@index')->name('cron');

// 支付同步通知
Route::any('respond.php', 'RespondController@index')->name('respond');
// 支付异步通知
Route::any('notify/{code?}', 'RespondController@notify')->name('notify');
// 退款异步通知
Route::any('notify_refound/{code?}', 'RespondController@notify_refound')->name('notify_refound');

// 输出二维码
Route::any('qrcode.php', 'QrcodeController@index')->name('qrcode');

// app扫码登录
Route::group(['prefix' => 'appqrcode'], function () {
    Route::any('/', 'AppQrcodeController@index')->name('appqrcode.index');           // 生成二维码
    Route::any('getqrcode', 'AppQrcodeController@getqrcode')->name('appqrcode.getqrcode'); // 二维码路径
    Route::any('getting', 'AppQrcodeController@getting')->name('appqrcode.getting'); // 轮询前端探查
});

//条形码
Route::any('barcodegen.php', 'BarcodegenController@index')->name('barcodegen');

Route::any('return_images.php', 'ReturnImagesController@index')->name('return_images');
Route::any('sdcms.cn.php', 'SdController@index')->name('sdcms.cn');
Route::any('search.php', 'SearchController@index')->name('search');
Route::any('tag-{keywords}.html', 'SearchController@index')
    ->where(['keywords' => '.*']);
// 秒杀
Route::any('seckill.php', 'SeckillController@index')->name('seckill');
Route::any('seckill.html', 'SeckillController@index');
Route::any('seckill-{id}-{act}.html', 'SeckillController@index')
    ->where(['id' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('seckill-{cat_id}.html', 'SeckillController@index')
    ->where(['cat_id' => '[0-9]+']);

Route::any('single_sun.php', 'SingleSunController@index')->name('single_sun');
Route::any('single_sun_images.php', 'SingleSunImagesController@index')->name('single_sun_images');
Route::any('sitemaps.php', 'SitemapsController@index')->name('sitemaps');
// 夺宝奇兵
Route::any('snatch.php', 'SnatchController@index')->name('snatch');
Route::any('snatch.html', 'SnatchController@index');
Route::any('snatch-{id}.html', 'SnatchController@index')
    ->where(['id' => '[0-9]+']);
// 店铺街
Route::any('store_street.php', 'StoreStreetController@index')->name('store_street');
Route::any('store_street.html', 'StoreStreetController@index');

Route::any('suggestions.php', 'SuggestionsController@index')->name('suggestions');
Route::any('suggestions.html', 'SuggestionsController@index')->name('suggestions');
Route::any('tag_cloud.php', 'TagCloudController@index')->name('tag_cloud');
Route::any('topic.php', 'TopicController@index')->name('topic');
Route::any('topic.html', 'TopicController@index')->name('topic');
Route::any('trade_snapshot.php', 'TradeSnapshotController@index')->name('trade_snapshot');
Route::any('user.php', 'UserController@index')->name('user');
Route::any('user.html', 'UserController@index')->name('user');
Route::any('user-{act}.html', 'UserController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_wholesale.php', 'UserWholesaleController@index')->name('user_wholesale');
Route::any('user_wholesale-{act}.html', 'UserWholesaleController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_order.php', 'UserOrderController@index')->name('user_order');
Route::any('user_order-{act}.html', 'UserOrderController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_address.php', 'UserAddressController@index')->name('user_address');
Route::any('user_address-{act}.html', 'UserAddressController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_collect.php', 'UserCollectController@index')->name('user_collect');
Route::any('user_collect-{act}.html', 'UserCollectController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_message.php', 'UserMessageController@index')->name('user_message');
Route::any('user_message-{act}.html', 'UserMessageController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_crowdfund.php', 'UserCrowdfundController@index')->name('user_crowdfund');
Route::any('user_crowdfund-{act}.html', 'UserCrowdfundController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_activity.php', 'UserActivityController@index')->name('user_activity');
Route::any('user_activity-{act}.html', 'UserActivityController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('user_baitiao.php', 'UserBaitiaoController@index')->name('user_baitiao');
Route::any('user_baitiao-{act}.html', 'UserBaitiaoController@index')
    ->where('act', '([a-zA-Z])+([^-]*)');

Route::any('vote.php', 'VoteController@index')->name('vote');

//商城安装
Route::group(['namespace' => 'Install', 'prefix' => 'install'], function () {
    Route::any('/', 'InstallController@index')->name('install');
    Route::any('cloud', 'CloudController@index')->name('install/cloud');
});

// 微信
Route::group(['namespace' => 'Wechat', 'prefix' => 'wechat'], function () {
    Route::any('/', 'WechatController@index')->name('wechat');
    Route::any('plugin_show', 'WechatController@plugin_show')->name('wechat/plugin_show');
    Route::any('plugin_action', 'WechatController@plugin_action')->name('wechat/plugin_action');
    Route::any('market_show', 'WechatController@market_show')->name('wechat/market_show');
    // 微信api
    Route::any('api', 'ApiController@index')->name('wechat/api/index');
    Route::any('jssdk', 'ApiController@jssdk')->name('wechat/api/jssdk');
});

// 供应链 start
Route::any('wholesale.php', 'WholesaleController@index')->name('wholesale');
Route::any('wholesale.html', 'WholesaleController@index');
Route::any('wholesale_cat.php', 'WholesaleCatController@index')->name('wholesale_cat');
Route::any('wholesale_cat-{id}-status{status}-{act}.html', 'WholesaleCatController@index')
    ->where(['id' => '[0-9]+', 'status' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('wholesale_cat-{id}-status{status}.html', 'WholesaleCatController@index')
    ->where(['id' => '[0-9]+', 'status' => '[0-9]+']);
Route::any('wholesale_cat-{id}-{act}.html', 'WholesaleCatController@index')
    ->where(['id' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('wholesale_cat-{id}.html', 'WholesaleCatController@index')
    ->where(['id' => '[0-9]+']);

Route::any('wholesale_flow.php', 'WholesaleFlowController@index')->name('wholesale_flow');
Route::any('wholesale_goods.php', 'WholesaleGoodsController@index')->name('wholesale_goods');
Route::any('wholesale_goods-{id}.html', 'WholesaleGoodsController@index')
    ->where(['id' => '[0-9]+']);
Route::any('wholesale_purchase.php', 'WholesalePurchaseController@index')->name('wholesale_purchase');
Route::any('wholesale_purchase-{id}-{act}.html', 'WholesalePurchaseController@index')
    ->where(['id' => '[0-9]+', 'act' => '([a-zA-Z])+([^-]*)']);
Route::any('wholesale_purchase-{id}.html', 'WholesalePurchaseController@index')
    ->where(['id' => '[0-9]+']);
Route::any('wholesale_search.php', 'WholesaleSearchController@index')->name('wholesale_search');
Route::any('wholesale_suppliers.php', 'WholesaleSuppliersController@index')->name('wholesale_suppliers');
// 供应商入驻
Route::any('wholesale_apply.php', 'WholesaleApplyController@index')->name('wholesale_apply');
// 供应链 end

Route::any('wxpay_native_callback.php', 'WxpayNativeCallbackController@index')->name('wxpay_native_callback');
Route::any('editor.php', 'EditorController@index')->name('editor');
Route::any('calendar.php', 'CalendarController@index')->name('calendar');
Route::any('sms.php', 'SmsController@index')->name('sms');

// 物流跟踪
Route::get('tracker', 'TrackerController@mobile')->name('tracker');
Route::get('tracker_shipping', 'TrackerController@index');

// 客服
Route::group(['namespace' => 'Chat', 'prefix' => 'kefu'], function () {
    Route::any('/', 'AdminController@index')->name('kefu.admin.index');
    Route::any('admin/history', 'AdminController@history')->name('kefu.admin.history');
    Route::any('admin/searchhistory', 'AdminController@searchhistory')->name('kefu.admin.searchhistory');
    Route::any('admin/change_message_status', 'AdminController@changeMessageStatus')->name('kefu.admin.change_message_status');
    Route::any('admin/get_goods', 'AdminController@getGoods')->name('kefu.admin.get_goods');
    Route::any('admin/get_store', 'AdminController@getStore')->name('kefu.admin.get_store');
    Route::any('admin/add_reply', 'AdminController@addReply')->name('kefu.admin.add_reply');
    Route::any('admin/remove_reply', 'AdminController@removeReply')->name('kefu.admin.remove_reply');
    Route::any('admin/change_status', 'AdminController@changeStatus')->name('kefu.admin.change_status');
    Route::any('admin/insert_user_reply', 'AdminController@insertUserReply')->name('kefu.admin.insert_user_reply');
    Route::any('admin/take_user_reply', 'AdminController@takeUserReply')->name('kefu.admin.take_user_reply');
    Route::any('admin/insert_user_leave_reply', 'AdminController@insertUserLeaveReply')->name('kefu.admin.insert_user_leave_reply');
    Route::any('admin/user_leave_reply', 'AdminController@userLeaveReply')->name('kefu.admin.user_leave_reply');
    Route::any('admin/dialog_info', 'AdminController@dialogInfo')->name('kefu.admin.dialog_info');
    Route::any('admin/close_dialog', 'AdminController@closeDialog')->name('kefu.admin.close_dialog');
    Route::any('admin/createdialog', 'AdminController@createdialog')->name('kefu.admin.createdialog');
    Route::any('admin/close_old_dialog', 'AdminController@closeOldDialog')->name('kefu.admin.close_old_dialog');
    Route::any('admin/change_login', 'AdminController@changeLogin')->name('kefu.admin.change_login');
    Route::any('admin/storage_message', 'AdminController@storageMessage')->name('kefu.admin.storage_message');
    Route::any('admin/change_msg_info', 'AdminController@changeMsgInfo')->name('kefu.admin.change_msg_info');
    Route::any('admin/change_new_msg_info', 'AdminController@changeNewMsgInfo')->name('kefu.admin.change_new_msg_info');
    Route::any('admin/getreply', 'AdminController@getreply')->name('kefu.admin.getreply');
    Route::any('admin/upload_image', 'AdminController@uploadImage')->name('kefu.admin.upload_image');
    Route::any('admin/trans_message', 'AdminController@transMessage')->name('kefu.admin.trans_message');

    Route::any('adminp/mobile', 'AdminpController@mobile')->name('kefu.adminp.mobile');
    Route::any('adminp/index', 'AdminpController@index')->name('kefu.adminp.index');
    Route::any('adminp/init_info', 'AdminpController@initInfo')->name('kefu.adminp.init_info');
    Route::any('adminp/change_message_status', 'AdminpController@changeMessageStatus')->name('kefu.adminp.change_message_status');
    Route::any('adminp/visit', 'AdminpController@visit')->name('kefu.adminp.visit');
    Route::any('adminp/chat_list', 'AdminpController@chatList')->name('kefu.adminp.chat_list');
    Route::any('adminp/goods_info', 'AdminpController@goodsInfo')->name('kefu.adminp.goods_info');
    Route::any('adminp/service_info', 'AdminpController@serviceInfo')->name('kefu.adminp.service_info');
    Route::any('adminp/logout', 'AdminpController@logout')->name('kefu.adminp.logout');

    Route::any('index', 'IndexController@index')->name('kefu.index.index');
    Route::any('index/order_list', 'IndexController@orderList')->name('kefu.index.order_list');
    Route::any('index/chat_list', 'IndexController@chatList')->name('kefu.index.chat_list');
    Route::any('index/single_chat_list', 'IndexController@singleChatList')->name('kefu.index.single_chat_list');
    Route::any('index/service_chat_data', 'IndexController@serviceChatData')->name('kefu.index.service_chat_data');
    Route::any('index/send_image', 'IndexController@sendImage')->name('kefu.index.send_image');

    Route::any('login/index', 'LoginController@index')->name('kefu.login.index');
    Route::any('login/logout', 'LoginController@logout')->name('kefu.login.logout');
    Route::any('login/captcha', 'LoginController@captcha')->name('kefu.login.captcha');
});

// 微商城
Route::namespace('Mobile')->prefix('mobile')->group(function () {
    Route::get('/', 'IndexController@mobile')->name('mobile');
    Route::get('oauth/callback', 'IndexController@callback')->name('mobile.oauth.callback');
    Route::get('respond', 'IndexController@respond')->name('mobile.pay.respond');
});

// 过滤词
Route::prefix('filter')->name('filter/')->group(function () {
    // 记录
    Route::any('updatelogs', 'FilterWordsController@updatelogs')->name('updatelogs');
});
