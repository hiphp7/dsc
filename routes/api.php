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

/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/

Route::get('/', 'App\Api\Fourth\Controllers\MonitorController@index');

Route::namespace('App\Api\Fourth\Controllers')->prefix('v4')->group(function () {

    Route::get('swagger', 'SwaggerController@index');

    // home
    Route::get('home', 'IndexController@index');
    Route::get('app/home', 'IndexController@home');
    // 网店配置
    Route::get('shop/config', 'IndexController@shopConfig');
    // 语言包
    Route::get('shop/lang', 'IndexController@shopLang');

    // account 账户
    Route::prefix('account')->group(function () {
        // 账户概要
        Route::get('/', 'AccountController@index')->name('api.account.index');
        // 申请记录
        Route::get('replylog', 'AccountController@replylog')->name('api.account.replylog');
        // 账户明细
        Route::get('accountlog', 'AccountController@accountlog')->name('api.account.accountlog');
        // 资金提现
        Route::get('reply', 'AccountController@reply')->name('api.account.reply');
        // 账户充值
        Route::get('deposit', 'AccountController@deposit')->name('api.account.deposit');
        // 充值提现操作
        Route::post('account', 'AccountController@account')->name('api.account.account');
        // 个人积分明细
        Route::get('paypoints', 'AccountController@paypoints')->name('api.account.paypoints');
    });

    //realname 实名认证
    Route::prefix('realname')->group(function () {
        // 实名认证详情
        Route::get('/', 'CertificationController@index')->name('api.realname.index');
        // 添加实名认证详情
        Route::post('store', 'CertificationController@store')->name('api.realname.store');
        // 实名认证修改
        Route::put('{realname}', 'CertificationController@update')->name('api.realname.update');
    });

    // accountsafe 账户安全
    Route::prefix('accountsafe')->group(function () {
        // 账户安全首页
        Route::get('/', 'AccountSafeController@index')->name('api.accountsafe.index');

        // 启用支付密码
        Route::get('add_paypwd', 'AccountSafeController@addPaypwd')->name('api.accountsafe.addpaypwd');
        // 更新支付密码
        Route::post('edit_paypwd', 'AccountSafeController@editPaypwd')->name('api.accountsafe.editpaypwd');
    });

    // bank 用户银行账号
    Route::prefix('bank-card')->group(function () {
        // 银行账号
        Route::get('/', 'BankCardController@index')->name('api.bankcard.index');
        // 添加银行账号
        Route::post('store', 'BankCardController@store')->name('api.bankcard.store');
        // 更新单条银行账号
        Route::put('{bank}', 'BankCardController@update')->name('api.bankcard.update');
    });

    // activity 活动
    Route::prefix('activity')->group(function () {
        // 列表
        Route::get('/', 'ActivityController@index')->name('api.activity.index');
        // 详情
        Route::get('show', 'ActivityController@show')->name('api.activity.show');
        // 商品
        Route::post('goods', 'ActivityController@goods')->name('api.activity.goods');
        // 凑单
        Route::get('coudan', 'ActivityController@coudan')->name('api.activity.coudan');
    });

    // address 收货地址
    Route::prefix('address')->group(function () {
        // 所有地址列表
        Route::get('/', 'AddressController@index')->name('api.address.index');
        // 同步微信收货地址
        Route::post('wximport', 'AddressController@wximport')->name('api.address.wximport');
        // 添加地址
        Route::post('store', 'AddressController@store')->name('api.address.store');
        // 单条地址信息
        Route::post('show', 'AddressController@show')->name('api.address.show');
        // 更新单条地址信息
        Route::put('update', 'AddressController@update')->name('api.address.update');
        // 删除地址
        Route::delete('destroy', 'AddressController@destroy')->name('api.address.destroy');
        // 设置默认地址
        Route::post('default', 'AddressController@setDefault')->name('api.address.default');
    });

    // article 文章
    Route::prefix('article')->group(function () {
        // 分类列表
        Route::post('/', 'ArticleController@index')->name('api.article.index');
        // 文章列表
        Route::post('list', 'ArticleController@list')->name('api.article.list');
        // 详情
        Route::post('show', 'ArticleController@show')->name('api.article.show');
        // 分类详情
        Route::post('category', 'ArticleController@category')->name('api.article.category');
        // 提交评论
        Route::post('comment', 'ArticleController@comment')->name('api.article.comment');
        // 评论列表
        Route::get('commentlist', 'ArticleController@commentlist')->name('api.article.commentlist');
        // 点赞
        Route::get('like', 'ArticleController@like')->name('api.article.like');
        // 微信文章素材
        Route::get('wechat_media', 'ArticleController@wechat_media')->name('api.article.wechat_media');
    });

    // auction 拍卖
    Route::prefix('auction')->group(function () {
        // 列表
        Route::get('/', 'AuctionController@index')->name('api.auction.index');
        // 详情
        Route::get('detail', 'AuctionController@detail')->name('api.auction.detail');
        // 记录
        Route::get('log', 'AuctionController@log')->name('api.auction.log');
        // 拍卖
        Route::get('bid', 'AuctionController@bid')->name('api.auction.bid');
        // 购买
        Route::get('buy', 'AuctionController@buy')->name('api.auction.buy');
        // 参与拍卖列表
        Route::get('auction_list', 'AuctionController@auctionList')->name('api.auction.auctionList');


    });

    // bargain 砍价
    Route::prefix('bargain')->group(function () {
        // 首页
        Route::get('/', 'BargainController@index')->name('api.bargain.index');
        // 商品列表
        Route::get('goods', 'BargainController@goods')->name('api.bargain.goods');
        // 详情
        Route::get('detail', 'BargainController@detail')->name('api.bargain.detail');
        // 改变属性、数量时重新计算商品价格
        Route::post('property', 'BargainController@property')->name('api.bargain.property');
        // 记录
        Route::get('log', 'BargainController@log')->name('api.bargain.log');
        // 砍价
        Route::get('bid', 'BargainController@bid')->name('api.bargain.bid');
        // 购买
        Route::get('buy', 'BargainController@buy')->name('api.bargain.buy');
        // 我参与的砍价
        Route::get('my_buy', 'BargainController@myBuy')->name('api.bargain.mybuy');

    });

    // bonus 红包
    Route::prefix('bonus')->group(function () {
        // 全部
        Route::get('/', 'BonusController@index')->name('api.bonus.index');
        // 领红包
        Route::post('receive', 'BonusController@receive')->name('api.bonus.receive');
        // 添加
        Route::post('store', 'BonusController@store')->name('api.bonus.store');
        // 会员中心红包
        Route::get('bonus', 'BonusController@bonus')->name('api.bonus.bonus');
        // 提交订单页红包列表
        Route::get('flowbonus', 'BonusController@flowbonus')->name('api.bonus.flowbonus');
    });

    // brand 品牌
    Route::prefix('brand')->group(function () {
        // 列表
        Route::post('/', 'BrandController@index')->name('api.brand.index');
        // 详情
        Route::post('detail', 'BrandController@detail');
        // 商品
        Route::post('goodslist', 'BrandController@goodslist');
        // 品牌列表
        Route::post('brandlist', 'BrandController@brandlist');
    });

    // cart 购物车
    Route::prefix('cart')->group(function () {
        // 添加商品
        Route::post('add', 'CartController@add')->name('api.cart.add');
        // 添加超值礼包
        Route::post('addpackage', 'CartController@addPackage')->name('api.cart.addpackage');
        // 优惠活动（赠品）列表
        Route::post('giftlist', 'CartController@giftList')->name('api.cart.giftlist');
        // 添加优惠活动（赠品）到购物车
        Route::post('addGiftCart', 'CartController@addGiftCart')->name('api.cart.addGiftCart');
        // 商品列表
        Route::post('goodslist', 'CartController@goodsList')->name('api.cart.goodslist');
        // 更新
        Route::put('{goods}', 'CartController@update')->name('api.cart.update');
        // 购物车总价
        Route::post('amount', 'CartController@amount')->name('api.cart.amount');
        // 删除商品
        Route::post('deletecart', 'CartController@deleteCart')->name('api.cart.deletecart');
        // 清空购物车
        Route::post('clearcart', 'CartController@clearCart')->name('api.cart.clearcart');
        // 检查购物车选中商品
        Route::post('cartvalue', 'CartController@cartValue')->name('api.cart.cartvalue');
        // 选择购物车商品
        Route::post('checked', 'CartController@checked')->name('api.cart.checked');
        // 更新购物车
        Route::post('update', 'CartController@update')->name('api.cart.update');
        // 购物车选择促销活动
        Route::post('getfavourable', 'CartController@getFavourable')->name('api.cart.getfavourable');
        // 购物车切换可选促销
        Route::post('changefav', 'CartController@changefav')->name('api.cart.changefav');
        // 购物车领取优惠券列表
        Route::post('getCoupons', 'CartController@getCoupons')->name('api.cart.getCoupons');
        // 添加套餐组合商品（配件）到购物车（临时）
        Route::post('addToCartCombo', 'CartController@addToCartCombo')->name('api.cart.addToCartCombo');
        // 删除套餐组合购物车商品（配件）（临时）
        Route::post('delInCartCombo', 'CartController@delInCartCombo')->name('api.cart.delInCartCombo');
        // 套餐组合商品（配件）加入购物车 cart 最终
        Route::post('addToCartGroup', 'CartController@addToCartGroup')->name('api.cart.addToCartGroup');
        // 购物车商品数量
        Route::get('cartNum', 'CartController@cartNum')->name('api.cart.cartNum');

        // 更新到店购物车
        Route::post('offline/update', 'CartOfflineController@update')->name('api.cart.offline.update');

    });

    // cashier desk 收银台
    Route::prefix('cashier_desk')->group(function () {

    });

    // catalog 分类
    Route::prefix('catalog')->group(function () {
        // 列表
        Route::get('list/{catalog?}', 'CatalogController@index')->name('api.catalog.index');
        // 详情
        Route::get('{catalog}/detail', 'CatalogController@show')->name('api.catalog.show');
        // 价格区间
        Route::get('{catalog}/grade', 'CatalogController@grade')->name('api.catalog.grade');
        // 属性
        Route::get('{catalog}/attribute', 'CatalogController@attribute')->name('api.catalog.attribute');
        // 分类下商品
        Route::post('goodslist', 'CatalogController@goodslist')->name('api.catalog.goodslist');
        // 分类筛选品牌
        Route::post('brandlist', 'CatalogController@brandlist')->name('api.catalog.brandlist');
        // 店铺下面分类品牌
        Route::post('shopcat', 'CatalogController@shopcat')->name('api.catalog.shopcat');
    });

    // chat 客服
    Route::prefix('chat')->group(function () {
        Route::get('/', 'ChatController@index')->name('api.chat.index');
    });

    // collect 收藏
    Route::prefix('collect')->group(function () {
        // 收藏店铺列表
        Route::get('shop', 'CollectController@shop')->name('api.collect.shop');
        // 收藏店铺
        Route::post('collectshop', 'CollectController@collectshop')->name('api.collect.collectshop');
        // 关注商品列表
        Route::get('goods', 'CollectController@goods')->name('api.collect.goods');
        // 关注商品
        Route::post('collectgoods', 'CollectController@collectgoods')->name('api.collect.collectgoods');
    });

    // comment 评论
    Route::prefix('comment')->group(function () {
        // 商品评论数量
        Route::post('title', 'CommentController@title')->name('api.comment.title');
        // 商品评论列表
        Route::post('goods', 'CommentController@goods')->name('api.comment.goods');
        // 评论商品
        //Route::post('goods/create', 'CommentController@goodsComment')->name('api.comment.goodsComment');
        // 文章评论
        //Route::get('article', 'CommentController@article')->name('api.comment.article');
        // 评论文章
        //Route::post('article/create', 'CommentController@articleComment')->name('api.comment.articleComment');
        // 待评论列表
        Route::post('commentlist', 'CommentController@commentlist')->name('api.comment.commentlist');
        // 商品评论页
        Route::post('addcomment', 'CommentController@ordergoods')->name('api.comment.ordergoods');
        // 评论商品
        Route::post('addgoodscomment', 'CommentController@addgoodscomment')->name('api.comment.addgoodscomment');
    });

    // coupon 优惠券
    Route::prefix('coupon')->group(function () {
        // 前台领取列表 - 好券集市
        Route::get('/', 'CouponController@index')->name('api.coupon.index');
        // 前台领取列表 - 任务集市
        Route::get('couponsgoods', 'CouponController@couponsGoods')->name('api.coupon.couponsGoods');
        // 领券
        Route::post('receive', 'CouponController@receive')->name('api.coupon.receive');
        // 商品
        Route::get('goods', 'CouponController@goods')->name('api.coupon.goods');
        // 会员中心优惠券
        Route::get('coupon', 'CouponController@coupon')->name('api.coupon.coupon');
    });

    // crowd funding 众筹
    Route::prefix('crowd_funding')->group(function () {
        // 首页
        Route::get('/', 'CrowdFundingController@index')->name('api.crowdfunding.index');
        // 列表
        Route::get('goods', 'CrowdFundingController@goods')->name('api.crowdfunding.goods');
        // 详情
        Route::get('show', 'CrowdFundingController@show')->name('api.crowdfunding.show');
        // 关注
        Route::get('focus', 'CrowdFundingController@focus')->name('api.crowdfunding.focus');
        // 发布话题
        Route::post('topic', 'CrowdFundingController@topic')->name('api.crowdfunding.topic');
        // 选择方案
        Route::get('property', 'CrowdFundingController@property')->name('api.crowdfunding.property');
        // 众筹描述
        Route::get('properties', 'CrowdFundingController@properties')->name('api.crowdfunding.properties');
        // 话题
        Route::get('topic_list', 'CrowdFundingController@topicList')->name('api.crowdfunding.topicList');
        // 订单确认
        Route::get('checkout', 'CrowdFundingController@checkout')->name('api.crowdfunding.checkout');
        // 提交订单
        Route::get('done', 'CrowdFundingController@done')->name('api.crowdfunding.done');

        // 众筹中心
        Route::get('user', 'CrowdFundingController@user')->name('api.crowdfunding.user');
        // 众筹中心项目推荐
        Route::get('crowd_best', 'CrowdFundingController@crowdBest')->name('api.crowdfunding.crowdBest');
        // 我的关注
        Route::get('my_focus', 'CrowdFundingController@myFocus')->name('api.crowdfunding.myFocus');
        // 我的支持
        Route::get('crowd_buy', 'CrowdFundingController@crowdBuy')->name('api.crowdfunding.crowdBuy');
        // 我的订单
        Route::get('order', 'CrowdFundingController@order')->name('api.crowdfunding.order');
        // 订单详情
        Route::get('detail', 'CrowdFundingController@detail')->name('api.crowdfunding.detail');
    });

    // discover 发现
    Route::prefix('discover')->group(function () {
        // 聚合
        Route::post('/', 'DiscoverController@index')->name('api.discover.index');
        // 列表
        Route::post('list', 'DiscoverController@list')->name('api.discover.list');
        // 我的帖子列表
        Route::post('mylist', 'DiscoverController@mylist')->name('api.discover.mylist');
        // 详情
        Route::post('detail', 'DiscoverController@detail')->name('api.discover.detail');
        // 评论列表
        Route::post('commentlist', 'DiscoverController@commentlist')->name('api.discover.commentlist');
        // 提交评论
        Route::post('comment', 'DiscoverController@comment')->name('api.discover.comment');
        // 我的帖子
        Route::post('my', 'DiscoverController@my')->name('api.discover.my');
        // 回复我的
        Route::post('reply', 'DiscoverController@reply')->name('api.discover.reply');
        // 发帖信息
        Route::post('show', 'DiscoverController@show')->name('api.discover.show');
        // 发帖
        Route::post('create', 'DiscoverController@create')->name('api.discover.create');
        // 帖子点赞
        Route::post('like', 'DiscoverController@like')->name('api.discover.like');
        // 帖子删除
        Route::delete('delete', 'DiscoverController@delete')->name('api.discover.delete');
    });

    // drp 分销
    Route::prefix('drp')->group(function () {
        // 分销 -- 申请注册店铺页
        Route::post('application', 'DistributionController@application')->name('api.drp.application');
        // 分销权益卡 1.4.1 add
        Route::post('drpcard', 'DistributionController@drpcard')->name('api.drp.drpcard');
        // 会员权益卡信息 1.4.1 add
        Route::get('rightscard', 'DistributionController@rightscard')->name('api.drp.rightscard');
        // 会员权益卡绑定的权益列表信息 1.4.1 add
        Route::get('rightscardlist', 'DistributionController@rightscardlist')->name('api.drp.rightscardlist');
        // 申请成为分销商统一提交页面 （重新领取）
        Route::post('apply', 'DistributionController@apply')->name('api.drp.apply');
        // 分销权益卡 续费 renew
        Route::post('renew', 'DistributionController@renew')->name('api.drp.renew');
        // 分销 -- h5购买流程 收银台
        Route::post('purchasepay', 'DistributionController@purchasepay')->name('api.drp.purchasepay');
        // 分销 -- h5 、app 切换支付方式
        Route::post('changepayment', 'DistributionController@changepayment')->name('api.drp.changepayment');
        // 分销 -- wxapp 小程序购买流程 收银台
        Route::post('wxapppurchasepay', 'DistributionController@wxapppurchasepay')->name('api.drp.wxapppurchasepay');
        // 分销 -- wxapp 小程序微信支付 发起支付
        Route::post('wxappchangepayment', 'DistributionController@wxappchangepayment')->name('api.drp.wxappchangepayment');
        // 分销 -- 分销中心
        Route::get('/', 'DistributionController@index')->name('api.drp.index');
        // 分销 -- 我的微店
        Route::get('my_shop', 'DistributionController@my_shop')->name('api.drp.my_shop');
        // 分销 -- 查看店铺
        Route::get('shop', 'DistributionController@shop')->name('api.drp.shop');
        // 分销 -- 店铺商品
        Route::get('shop_goods', 'DistributionController@shopGoods')->name('api.drp.shopGoods');
        // 分销 -- 店铺设置
        Route::get('show', 'DistributionController@show')->name('api.drp.show');
        // 分销 -- 设置头像
        Route::post('avatar', 'DistributionController@avatar')->name('api.drp.avatar');
        // 分销 -- 更新店铺设置
        Route::put('update', 'DistributionController@update')->name('api.drp.update');
        // 分销 -- 佣金转出页面
        Route::get('trans', 'DistributionController@trans')->name('api.drp.trans');
        // 分销 -- 佣金转到余额
        Route::post('transferred', 'DistributionController@transFerred')->name('api.drp.transferred');
        // 分销 -- 店铺订单
        Route::get('order', 'DistributionController@order')->name('api.drp.order');
        // 分销 -- 店铺订单详情
        Route::get('order_detail', 'DistributionController@orderDetail')->name('api.drp.orderDetail');
        // 分销 -- 我的团队
        Route::get('team', 'DistributionController@team')->name('api.drp.team');
        // 分销 -- 团队详情
        Route::get('teamdetail', 'DistributionController@teamDetail')->name('api.drp.teamdetail');
        // 分销 -- 下线会员
        Route::get('offline_user', 'DistributionController@offlineUser')->name('api.drp.offlineUser');
        // 分销 -- 我的名片
        Route::get('user_card', 'DistributionController@userCard')->name('api.drp.userCard');
        // 分销 -- 分销排行
        Route::get('rank_list', 'DistributionController@rankList')->name('api.drp.rankList');
        // 分销 -- 佣金明细
        Route::get('drp_log', 'DistributionController@drpLog')->name('api.drp.drpLog');
        // 分销 -- 文章
        Route::get('news', 'DistributionController@news')->name('api.drp.news');
        // 分销 -- 分销模式分类选择
        Route::get('cartlist', 'DistributionController@cartlist')->name('api.drp.cartlist');
        // 分销 -- 分销模式添加代言商品分类
        Route::get('addcart', 'DistributionController@addcart')->name('api.drp.addcart');
        // 分销 -- 分销模式分类下商品
        Route::get('drpgoods', 'DistributionController@drpgoods')->name('api.drp.drpgoods');
        // 分销 -- 分销模式添加代言商品
        Route::get('addgoods', 'DistributionController@addgoods')->name('api.drp.addgoods');

    });

    // exchange 积分兑换
    Route::prefix('exchange')->group(function () {
        // 列表
        Route::get('/', 'ExchangeController@index')->name('api.exchange.index');
        // 详情
        Route::get('detail', 'ExchangeController@detail')->name('api.exchange.detail');
        // 商品
        Route::get('buy', 'ExchangeController@buy')->name('api.exchange.buy');
    });

    // feedback 留言
    Route::prefix('feedback')->group(function () {
        // 列表
        Route::get('/', 'FeedbackController@index')->name('api.feedback.index');
        // 提交
        Route::post('create', 'FeedbackController@store')->name('api.feedback.create');
    });

    // goods 商品
    Route::prefix('goods')->group(function () {
        // 列表
        Route::get('/', 'GoodsController@index')->name('api.goods.index');
        // 详情
        Route::post('show', 'GoodsController@show')->name('api.goods.show');
        // 促销商品
        Route::post('promotegoods', 'GoodsController@promotegoods')->name('api.goods.promoteGoods');
        // 切换属性价格
        Route::post('attrprice', 'GoodsController@attrprice')->name('api.goods.attrprice');
        // 猜你喜欢
        Route::post('goodsguess', 'GoodsController@goodsguess')->name('api.goods.goodsguess');
        // 分享海报
        Route::post('shareposter', 'GoodsController@shareposter')->name('api.goods.shareposter');
        // 组合套餐 配件
        Route::post('fittings', 'GoodsController@fittings')->name('api.goods.fittings');
        // 组合套餐 改变属性、数量时重新计算商品价格
        Route::post('fittingprice', 'GoodsController@fittingprice')->name('api.goods.fittingprice');
        // 商品视频列表
        Route::post('goodsvideo', 'GoodsController@goodsVideo')->name('api.goods.goodsvideo');
    });

    // group buy 团购
    Route::prefix('group_buy')->group(function () {
        // 列表
        Route::get('/', 'GroupBuyController@index')->name('api.groupbuy.index');
        // 详情
        Route::get('detail', 'GroupBuyController@detail')->name('api.groupbuy.detail');
        // 价格
        Route::get('price', 'GroupBuyController@price')->name('api.groupbuy.price');
        // 购买
        Route::get('buy', 'GroupBuyController@buy')->name('api.groupbuy.buy');
    });

    // history 最近浏览历史
    Route::prefix('history')->group(function () {
        // 浏览历史列表
        Route::get('index', 'HistoryController@index')->name('api.history.index');
        // 添加浏览历史
        Route::post('store', 'HistoryController@store')->name('api.history.store');
        // 清空浏览历史
        Route::delete('destroy', 'HistoryController@destroy')->name('api.history.destroy');
    });

    // invite 邀请
    Route::prefix('invite')->group(function () {
        Route::get('/', 'InviteController@index')->name('api.invite.index');
    });

    // invoice 发票
    Route::prefix('invoice')->group(function () {
        //发票详情
        Route::get('/', 'InvoiceController@index')->name('api.invoice.index');
        // 添加发票
        Route::post('store', 'InvoiceController@store')->name('api.invoice.store');
        // 更新单条发票信息
        Route::put('update', 'InvoiceController@update')->name('api.invoice.update');
        // 删除发票
        Route::delete('destroy', 'InvoiceController@destroy')->name('api.invoice.destroy');
    });

    // misc 杂项
    Route::prefix('misc')->group(function () {
        // captcha
        Route::get('captcha', 'CaptchaController@index')->name('api.misc.captcha');
        // region
        Route::get('region', 'RegionController@index')->name('api.misc.region');
        // sms
        Route::post('sms/send', 'SmsController@index')->name('api.misc.sms.send');
        Route::post('sms/verify', 'SmsController@verify')->name('api.misc.sms.verify');
        // position 定位
        Route::get('position', 'PositionController@index')->name('api.misc.position');
        Route::get('ip', 'PositionController@ip')->name('api.misc.ip');
    });

    // OAuth
    Route::prefix('oauth')->group(function () {
        // 生成
        Route::get('code', 'OAuthController@code')->name('api.oauth.code');
        // 回调
        Route::get('callback', 'OAuthController@callback')->name('api.oauth.callback');
        // 校验授权用户信息
        Route::post('check/auth', 'OAuthController@checkAuth')->name('api.oauth.check.auth');

        // 用户授权登录管理
        Route::get('bindList', 'OAuthController@bindList')->name('api.oauth.bindList');
        // 用户授权登录解绑
        Route::post('unbind', 'OAuthController@unbind')->name('api.oauth.unbind');
    });

    // 门店
    Route::prefix('offline-store')->group(function () {
        // 生成
        Route::get('list', 'OfflineStoreController@index')->name('api.offline-store.list');
    });

    // order 订单
    Route::prefix('order')->group(function () {
        // 订单列表
        Route::post('list', 'OrderController@list')->name('api.order.list');
        // 订单详情
        Route::post('detail', 'OrderController@detail')->name('api.order.detail');
        // 订单确认
        Route::post('confirm', 'OrderController@confirm')->name('api.order.confirm');
        // 订单取消
        Route::post('cancel', 'OrderController@cancel')->name('api.order.cancel');
        // 延迟收货申请
        Route::post('delay', 'OrderController@delay')->name('api.order.delay');
        // 订单删除
        Route::post('delete', 'OrderController@delete')->name('api.order.delete');
        // 订单还原
        Route::post('restore', 'OrderController@restore')->name('api.order.restore');
        // 订单跟踪 eg: http://dscmall.test/api/v4/order/tracker?type=shentong&postid=3372277341133
        Route::get('tracker', 'OrderController@tracker')->name('api.order.tracker');
        //发货单信息
        Route::get('tracker_order', 'OrderController@tracker_order')->name('api.order.tracker_order');
        // 退换货
        Route::get('refound', 'OrderController@refound')->name('api.order.refound');

    });

    // refound 退换货
    Route::prefix('refound')->group(function () {
        // 列表
        Route::get('/', 'RefoundController@index')->name('api.refound.index');
        // 商品列表
        Route::get('returngoods', 'RefoundController@returngoods')->name('api.refound.returngoods');
        // 详情
        // Route::get('info', 'RefoundController@info')->name('api.refound.info');
        // 提交
        Route::get('applyreturn', 'RefoundController@applyreturn')->name('api.refound.applyreturn');
        // 详情
        Route::get('returndetail', 'RefoundController@returndetail')->name('api.refound.returndetail');
        // 提交
        Route::post('submit_return', 'RefoundController@submit_return')->name('api.refound.submit_return');
        // 取消退换货
        Route::post('cancel', 'RefoundController@cancel')->name('api.refound.cancel');
        // 编辑退换货快递信息
        Route::post('edit_express', 'RefoundController@edit_express')->name('api.refound.edit_express');
        // 确认收货退换货订单
        Route::post('affirm_receive', 'RefoundController@affirm_receive')->name('api.refound.affirm_receive');
        // 激活退换货订单
        Route::post('active_return_order', 'RefoundController@active_return_order')->name('api.refound.active_return_order');
        // 删除退换货订单
        // Route::post('delete_return_order', 'RefoundController@delete_return_order')->name('api.refound.delete_return_order');
    });

    // package 超值礼包
    Route::prefix('package')->group(function () {
        Route::get('list', 'PackageController@index')->name('api.package.list');
    });

    // payment 支付
    Route::prefix('payment')->group(function () {
        // 列表
        Route::get('list', 'PaymentController@index')->name('api.payment.list');
        // 支付
        Route::get('code', 'PaymentController@code')->name('api.payment.code');
        // 支付通知（同步）
        Route::get('callback', 'PaymentController@callback')->name('api.payment.callback');
        // 支付通知（异步）
        Route::post('notify/{code?}/{type?}', 'PaymentController@notify')->name('api.payment.notify');
        // 退款通知（异步）
        Route::post('notify_refound/{code?}', 'PaymentController@notify_refound')->name('api.payment.notify_refound');
        // 收银台
        Route::get('onlinepay', 'PaymentController@onlinepay')->name('api.payment.onlinepay');
        // 切换支付
        Route::get('change_payment', 'PaymentController@change_payment')->name('api.payment.change_payment');
        // 切换App支付
        Route::get('change_app_payment', 'PaymentController@changeAppPayment')->name('api.payment.change_app_payment');
        // wxapp切换App支付
        Route::get('wxapp_change_app_payment', 'PaymentController@wxappChangeAppPayment')->name('api.payment.wxappChangeAppPayment');
    });

    // presale 预售
    Route::prefix('presale')->group(function () {
        // 聚合
        Route::get('/', 'PresaleController@index')->name('api.presale.index');
        // 列表
        Route::get('list', 'PresaleController@list')->name('api.presale.list');
        // 详情
        Route::get('detail', 'PresaleController@detail')->name('api.presale.detail');
        // 价格
        Route::get('price', 'PresaleController@price')->name('api.presale.price');
        // 购买
        Route::get('buy', 'PresaleController@buy')->name('api.presale.buy');
        // 新品发布
        Route::get('new', 'PresaleController@new')->name('api.presale.new');
    });

    // purchase 采购
    Route::prefix('purchase')->group(function () {
        // 聚合
        Route::get('/', 'PurchaseController@index')->name('api.purchase.index');
        // 类别
        Route::get('list', 'PurchaseController@list')->name('api.purchase.list');
        // 商品列表
        Route::get('goodslist', 'PurchaseController@goodslist')->name('api.purchase.goodslist');
        // 搜索结果列表
        Route::get('searchlist', 'PurchaseController@searchlist')->name('api.purchase.searchlist');
        // 加入购物车
        Route::get('addtocart', 'PurchaseController@addtocart')->name('api.purchase.addtocart');
        // 提交订单
        Route::get('down', 'PurchaseController@down')->name('api.purchase.down');
        // 购物车
        Route::get('cart', 'PurchaseController@cart')->name('api.purchase.cart');
        // 购物车数量
        Route::get('updatecartgoods', 'PurchaseController@updatecartgoods')->name('api.purchase.updatecartgoods');
        // 采购列表
        Route::get('show', 'PurchaseController@show')->name('api.purchase.show');
        // 采购列表
        Route::get('showdetail', 'PurchaseController@showdetail')->name('api.purchase.showdetail');
        // 商品
        Route::get('goods', 'PurchaseController@goods')->name('api.purchase.goods');
    });

    // qrpay 扫码付
    Route::prefix('qrpay')->group(function () {
        // 支付首页
        Route::any('/', 'QrpayController@index')->name('api.qrpay.index');
        // 支付
        Route::post('pay', 'QrpayController@pay')->name('api.qrpay.pay');
        // 通知（同步）
        Route::get('callback', 'QrpayController@callback')->name('api.qrpay.callback');
        // 通知（异步）
        Route::post('notify/{code?}/{id?}', 'QrpayController@notify')->name('api.qrpay.notify');
    });

    // seckill 秒杀
    Route::prefix('seckill')->group(function () {
        // 聚合
        Route::get('/', 'SeckillController@index')->name('api.seckill.index');
        // 聚合
        Route::get('time', 'SeckillController@time')->name('api.seckill.time');
        // 详情
        Route::get('detail', 'SeckillController@detail')->name('api.seckill.detail');
        // 价格
        //Route::get('price', 'SeckillController@price')->name('api.seckill.price');
        // 购买
        Route::get('buy', 'SeckillController@buy')->name('api.seckill.buy');
    });

    // shipping 配送
    Route::prefix('shipping')->group(function () {
        // 列表
        Route::get('/', 'ShippingController@index')->name('api.shipping.index');
        // 运费
        Route::get('amount', 'ShippingController@amount')->name('api.shipping.amount');
        // 商品运费
        Route::get('goodsshippingfee', 'ShippingController@goodsshippingfee')->name('api.shipping.goodsshippingfee');
    });

    // store 门店
    Route::prefix('store')->group(function () {
        // 店铺街分类列表
        Route::post('catlist', 'StoreController@catList')->name('api.store.catlist');
        // 分类店铺列表
        Route::post('catstorelist', 'StoreController@catStoreList')->name('api.store.catstorelist');
        // 店铺商品
        Route::post('storegoodslist', 'StoreController@storeGoodsList')->name('api.store.storegoodslist');
        // 详情
        Route::post('storedetail', 'StoreController@storeDetail')->name('api.store.storedetail');
        // 店铺品牌
        Route::post('storebrand', 'StoreController@storeBrand')->name('api.store.storebrand');
        // 地图
        Route::get('map', 'StoreController@map')->name('api.store.map');
        /** 关注店铺优惠券 */
        Route::post('storecoupons', 'StoreController@storeCoupons')->name('api.store.storecoupons');
    });

    // shop 店铺
    Route::prefix('shop')->group(function () {
        // 店铺街分类列表
        Route::post('catlist', 'ShopController@catList')->name('api.shop.catlist');
        // 分类店铺列表
        Route::post('catshoplist', 'ShopController@catShopList')->name('api.shop.catshoplist');
        // 店铺商品
        Route::post('shopgoodslist', 'ShopController@shopGoodsList')->name('api.shop.shopgoodslist');
        // 详情
        Route::post('shopdetail', 'ShopController@shopDetail')->name('api.shop.shopdetail');
        // 店铺品牌
        Route::post('shopbrand', 'ShopController@shopBrand')->name('api.shop.shopbrand');
        // 地图
        Route::get('map', 'ShopController@map')->name('api.shop.map');
    });

    // team 拼团
    Route::prefix('team')->group(function () {
        // 拼团首页
        Route::get('/', 'TeamController@index')->name('api.team.index');
        // 首页，频道下商品列表
        Route::get('goods', 'TeamController@goods')->name('api.team.goods');
        // 拼团频道页面
        Route::get('categories', 'TeamController@categories')->name('api.team.categories');
        // 下单提示轮播
        Route::get('virtual_order', 'TeamController@virtualOrder')->name('api.team.virtualOrder');
        // 拼团子频道商品列表
        Route::get('goods_list', 'TeamController@goodsList')->name('api.team.goodsList');
        // 拼团排行
        Route::get('team_ranking', 'TeamController@teamRanking')->name('api.team.teamRanking');
        // 商品详情
        Route::get('detail', 'TeamController@detail')->name('api.team.detail');
        // 商品改变属性、数量时重新计算商品价格
        Route::get('property', 'TeamController@property')->name('api.team.property');
        // 加入购物车
        Route::get('team_buy', 'TeamController@teamBuy')->name('api.team.teamBuy');
        // 等待成团
        Route::get('team_wait', 'TeamController@teamWait')->name('api.team.teamWait');
        // 拼团成员
        Route::get('team_user', 'TeamController@teamUser')->name('api.team.teamUser');
        // 我的拼团
        Route::get('team_order', 'TeamController@teamOrder')->name('api.team.teamOrder');

    });

    // APP
    Route::prefix('app')->group(function () {
        // APP 启动页广告
        Route::get('ad_position', 'AppController@ad_position')->name('api.app.ad_position');
    });
    // app 授权登录
    Route::prefix('appqrcode')->group(function () {
        // app回调信息
        Route::post('appuser', 'AppController@appuser')->name('api.appqrcode.appuser');
        // 确认扫码
        Route::post('scancode', 'AppController@scancode')->name('api.appqrcode.scancode');
        // 取消授权登录
        Route::post('cancel', 'AppController@cancel')->name('api.appqrcode.cancel');
    });


    // value_card 储值卡
    Route::prefix('valuecard')->group(function () {
        // 列表
        Route::get('/', 'ValueCardController@index')->name('api.valuecard.index');
        // 详情
        Route::get('detail', 'ValueCardController@detail')->name('api.valuecard.detail');
        // 充值卡充值
        Route::post('deposit', 'ValueCardController@deposit')->name('api.valuecard.deposit');
        // 绑定
        Route::post('addvaluecard', 'ValueCardController@addvaluecard')->name('api.valuecard.addvaluecard');
    });


    // topic 专题
    Route::prefix('topic')->group(function () {
        // 专题
        Route::get('/', 'TopicController@index')->name('api.topic.index');
        // 详情
        Route::get('detail', 'TopicController@detail')->name('api.topic.detail');
    });

    // trade 交易
    Route::prefix('trade')->group(function () {
        // 购物车选中商品
        Route::post('orderinfo', 'TradeController@orderinfo')->name('api.trade.orderinfo');
        // 使用红包
        Route::post('changebon', 'TradeController@changebon')->name('api.trade.changebon');
        // 使用优惠券
        Route::post('changecou', 'TradeController@changecou')->name('api.trade.changecou');
        // 储值卡
        Route::post('changecard', 'TradeController@changecard')->name('api.trade.changecard');
        // 消费积分
        Route::post('changeint', 'TradeController@changeint')->name('api.trade.changeint');
        // 使用余额
        Route::post('changesurplus', 'TradeController@changesurplus')->name('api.trade.changesurplus');
        // 切换开通购买权益卡
        Route::post('change_membership_card', 'TradeController@changeMembershipCard')->name('api.trade.change_membership_card');
        // 包装
        //Route::get('pack', 'TradeController@pack')->name('api.trade.pack');
        // 使用余额支付
        Route::any('balance', 'TradeController@balance')->name('api.trade.balance');
        // 自提点
        //Route::get('pick', 'TradeController@pick')->name('api.trade.pick');
        // 发票
        //Route::get('invoice', 'TradeController@invoice')->name('api.trade.invoice');
        // 提交
        Route::post('done', 'TradeController@done')->name('api.trade.done');
        // 选择在线支付方式
        Route::post('paycheck', 'TradeController@paycheck')->name('api.trade.paycheck');
        // 余额支付
        Route::post('balance', 'TradeController@balance')->name('api.trade.balance');
        // 再次购买
        Route::get('buyagain', 'TradeController@buyagain')->name('api.trade.buyagain');
    });

    // user 用户
    Route::prefix('user')->group(function () {
        // 注册
        Route::post('register', 'PassportController@register')->name('api.user.register');
        // 登录
        Route::post('login', 'PassportController@login')->name('api.user.login');
        Route::get('login_config', 'PassportController@loginConfig')->name('api.user.loginConfig');
        Route::post('fast-login', 'PassportController@fastLogin')->name('api.user.login.fast');
        // 社会化登录
        Route::get('oauth_list', 'PassportController@oauth_list')->name('api.user.oauth_list');
        // 重置密码
        Route::post('reset', 'PassportController@reset')->name('api.user.reset');
        // 聚合
        Route::get('home', 'UserController@index')->name('api.user.home');
        // 资料
        Route::get('profile/basic', 'UserController@basicProfileByMobile')->name('api.user.profile.basic');
        // 资料
        Route::get('profile', 'UserController@profile')->name('api.user.profile');
        // 保存资料
        Route::put('profile', 'UserController@update')->name('api.user.update');
        // 设置头像
        Route::get('avatar', 'UserController@avatar')->name('api.user.avatar');
        // 素材
        Route::post('material', 'MaterialController@uploads')->name('api.user.material');
        // 生成ecjia hash
        Route::post('ecjia-hash', 'UserController@ecjiaHash')->name('api.user.ecjia.hash');
        // 帮助
        Route::post('help', 'UserController@help')->name('api.user.help');
        // 小程序登录
        Route::prefix('wxapp')->group(function () {
            // 获取openId, sessionKey, unionId
            Route::post('session', 'WechatAppController@session')->name('api.user.wxapp.session');
            // 获取加密的手机号或unionId
            Route::post('decrypt', 'WechatAppController@decrypt')->name('api.user.wxapp.decrypt');
            // 快捷登录
            Route::post('login', 'WechatAppController@login')->name('api.user.wxapp.login');
        });
    });

    // visual 可视化
    Route::prefix('visual')->group(function () {
        // APP
        Route::post('/', 'VisualController@index')->name('api.visual.index');
        // 默认
        Route::post('default', 'VisualController@default')->name('api.visual.default');
        // 头部APP广告
        Route::post('appnav', 'VisualController@appnav')->name('api.visual.appnav');
        // 公告
        Route::post('article', 'VisualController@article')->name('api.visual.article');
        //分类
        Route::post('product', 'VisualController@product')->name('api.visual.product');
        // 选中的商品
        Route::post('checked', 'VisualController@checked')->name('api.visual.checked');
        // 秒杀
        Route::post('seckill', 'VisualController@seckill')->name('api.visual.seckill');
        // 店铺街
        Route::post('store', 'VisualController@store')->name('api.visual.store');
        // 店铺街详情
        Route::post('storein', 'VisualController@storein')->name('api.visual.storein');
        // 店铺街底部详情
        Route::post('storedown', 'VisualController@storedown')->name('api.visual.storedown');
        // 店铺街关注
        Route::post('addcollect', 'VisualController@addcollect')->name('api.visual.addcollect');
        // 展示
        Route::post('view', 'VisualController@view')->name('api.visual.view');
    });

    // wholesale 供求
    Route::prefix('suppliers')->group(function () {
        // 首页
        Route::get('/', 'SuppliersController@index')->name('api.suppliers.index');
        // 首页数据  轮播图 分类 限时抢购 分类商品
        Route::get('show', 'SuppliersController@show')->name('api.suppliers.show');
        // 供应商家主页
        Route::get('supplierhome', 'SuppliersController@supplierhome')->name('api.suppliers.supplierhome');
        // 供应商家商品列表
        Route::get('homelist', 'SuppliersController@homelist')->name('api.suppliers.homelist');
        // 搜索
        Route::get('search', 'SuppliersController@search')->name('api.suppliers.search');
        // 分类
        Route::get('category', 'SuppliersController@category')->name('api.suppliers.category');
        // 限时抢购
        Route::get('getlimit', 'SuppliersController@getlimit')->name('api.suppliers.getlimit');
        //分类商品
        Route::get('catgoods', 'SuppliersController@catgoods')->name('api.suppliers.catgoods');
        //分类商品
        Route::get('goodslist', 'SuppliersController@goodslist')->name('api.suppliers.goodslist');
        // 商品详情
        Route::get('detail', 'SuppliersController@detail')->name('api.suppliers.detail');
        // 获取属性库存
        Route::post('changenum', 'SuppliersController@changenum')->name('api.suppliers.changenum');
        // 改变属性、数量时重新计算商品价格
        Route::post('changeprice', 'SuppliersController@changeprice')->name('api.suppliers.changeprice');
        // 加入购物车
        Route::post('addtocart', 'SuppliersController@addtocart')->name('api.suppliers.addtocart');
        // 购物车
        Route::post('cart', 'SuppliersController@cart')->name('api.suppliers.cart');
        // 更新购物车
        Route::post('updatecart', 'SuppliersController@updatecart')->name('api.suppliers.updatecart');
        // 选中购物车商品
        Route::post('checked', 'SuppliersController@checked')->name('api.suppliers.checked');
        // 删除购物车
        Route::post('clearcart', 'SuppliersController@clearcart')->name('api.suppliers.clearcart');
        // 订单提交
        Route::post('flow', 'SuppliersController@flow')->name('api.suppliers.flow');
        // 提交
        Route::post('done', 'SuppliersController@done')->name('api.suppliers.done');
        // 求购信息列表
        Route::any('purchase/list', 'WholesalePurchaseController@list')->name('api.purchase.list');
        // 求购信息详情
        Route::get('purchase/info', 'WholesalePurchaseController@info')->name('api.purchase.info');
        // 订单列表
        Route::any('orderlist', 'SuppliersOrderController@orderlist')->name('api.suppliersorder.orderlist');
        // 确认订单
        Route::get('affirmorder', 'SuppliersOrderController@affirmorder')->name('api.suppliersorder.affirmorder');
        // 退换货订单列表
        Route::get('returnorderlist', 'SuppliersOrderController@returnorderlist')->name('api.suppliersorder.returnorderlist');
        // 退换货订单详情
        Route::get('returnorderdetail', 'SuppliersOrderController@returnorderdetail')->name('api.suppliersorder.returnorderdetail');
        // 订单商品详情
        Route::get('goodsorder', 'SuppliersOrderController@wholesalegoodsorder')->name('api.suppliersorder.goodsorder');
        // 退换货详情
        Route::get('applyreturn', 'SuppliersOrderController@applyreturn')->name('api.suppliersorder.applyreturn');
        // 退换货申请
        Route::post('submitreturn', 'SuppliersOrderController@submitreturn')->name('api.suppliersorder.submitreturn');
        // 删除已完成退换货订单
        Route::post('deletereturn', 'SuppliersOrderController@deletereturn')->name('api.suppliersorder.deletereturn');
        // 激活退换货订单
        Route::post('activationreturnorder', 'SuppliersOrderController@activationreturnorder')->name('api.suppliersorder.activationreturnorder');
        // 供求订单check
        Route::get('paycheck', 'SuppliersController@paycheck')->name('api.suppliers.paycheck');
        // 切换支付
        Route::get('change_payment', 'SuppliersController@change_payment')->name('api.suppliers.change_payment');
        // 余额支付
        Route::post('balance', 'SuppliersController@balance')->name('api.suppliers.balance');
        // 供应商入驻
        Route::get('apply', 'WholesaleApplyController@apply')->name('api.suppliers.apply');
        // 提交供应商入驻
        Route::post('do_apply', 'WholesaleApplyController@do_apply')->name('api.suppliers.do_apply');
    });

    // 微信JSSDK
    Route::prefix('wechat')->group(function () {
        Route::any('jssdk', 'IndexController@jssdk')->name('api.wechat.jssdk');
    });

    // 过滤词
    Route::prefix('filter')->name('api/filter/')->group(function () {
        // 记录
        Route::any('updatelogs', 'FilterWordsController@updatelogs')->name('updatelogs');
    });

    // 礼品卡
    Route::prefix('gift_gard')->group(function () {
        // 验证是否存在礼品卡
        Route::get('/', 'GiftGardController@index')->name('api.giftgard.index');
        // 礼品卡查询
        Route::get('check_gift', 'GiftGardController@checkGift')->name('api.giftgard.checkGift');
        // 礼品卡兑换列表
        Route::get('gift_list', 'GiftGardController@giftList')->name('api.giftgard.giftList');
        // 退出礼品卡
        Route::get('exit_gift', 'GiftGardController@exitGift')->name('api.giftgard.exitGift');
        // 提货
        Route::get('check_take', 'GiftGardController@checkTake')->name('api.giftgard.checkTake');
        // 我的提货
        Route::get('take_list', 'GiftGardController@takeList')->name('api.giftgard.takeList');
        // 确认收货
        Route::get('confim_goods', 'GiftGardController@confimGoods')->name('api.giftgard.confimGoods');

    });

    // 商家入驻
    Route::prefix('merchants')->group(function () {
        // 入驻商家信息
        Route::post('/', 'MerchantsController@index')->name('api.merchants.index');
        // 入驻须知
        Route::post('guide', 'MerchantsController@guide')->name('api.merchants.guide');
        // 同意协议
        Route::post('agree', 'MerchantsController@agree')->name('api.merchants.agree');
        // 入驻店铺信息
        Route::get('shop', 'MerchantsController@shop')->name('api.merchants.shop');
        // 提交入驻店铺信息
        Route::post('add_shop', 'MerchantsController@add_shop')->name('api.merchants.add_shop');
        // 获取下级类目
        Route::post('get_child_cate', 'MerchantsController@get_child_cate')->name('api.merchants.get_child_cate');
        // 添加详细类目
        Route::post('add_child_cate', 'MerchantsController@add_child_cate')->name('api.merchants.add_child_cate');
        // 删除详细类目
        Route::post('delete_child_cate', 'MerchantsController@delete_child_cate')->name('api.merchants.delete_child_cate');
        // 等待审核
        Route::get('audit', 'MerchantsController@audit')->name('api.merchants.audit');
    });

});
