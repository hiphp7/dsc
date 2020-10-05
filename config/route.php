<?php

return [
    // 文章
    'article' => [
		'index' => '/pages/article/index',
		'list' => '/pages/article/list?id=',
		'detail' => '/pages/article/detail?id=',
    ],

    // 分类页
    'catalog' => [
		'index' => '/pages/category/category',
    ],

    // 商品
    'goods' => [
        'list' => '/pages/goodslist/goodslist?id=',
 		'detail' => '/pages/goodsDetail/goodsDetail?id=',
    ],

    // 秒杀
    'seckill' => [
		'index' => '/pagesA/seckill/seckill',
		'detail' => '/pagesA/seckill/detail/detail?seckill_id=',
    ],

    // 购物车
    'cart' => [
		'index' => '/pages/cart/cart',
    ],

    // 会员中心
    'user' => [
		'index' => '/pages/user/user',
    ],

    // 店铺街
    'shop' => [
        'index' => '/pages/shop/shop',
        'home' => '/pages/shop/shopHome/shopHome?ru_id=',
        'detail' => '/pages/shop/shopDetail/shopDetail?id=',
    ],

    // 品牌:
    'brand' => [
        'index' => '/pages/brand/brand',
        'list' => '/pages/brand/list/list',
        'detail' => '/pages/brand/detail/detail?id=',
    ],

    // 社区圈子:
    'discover' => [
		'index' => '',
        //'index' => '/pages/discover/discover',
    ],

    // 预售:
    'presale' => [
        'index' => '/pagesA/presale/presale',
        'list' => '/pagesA/list/list?cat_id=',
        'detail' => '/pagesA/presale/detail/detail?act_id=',
    ],

    // 团购:
    'groupbuy' => [
		'index' => '/pagesA/groupbuy/groupbuy',
        'detail' => '/pagesA/groupbuy/detail/detail?id=',
    ],

    // 积分商城:
    'exchange' => [
		'index' => '/pagesA/exchange/exchange',
        'detail' => '/pagesA/exchange/detail/detail?id=',
    ],

    // 专题列表:
    'topic' => [
        'index' => '/pagesA/topic/topic',
        'detail' => '/pagesA/topic/detail/detail?id=',
    ],

    // 优惠活动:
    'activity' => [
		'index' => '/pagesA/activity/activity',
        'detail' => '/pagesA/activity/detail/detail?act_id=',
    ],

    // 礼品卡:
    'giftcard' => [
        'index' => '/pagesA/giftcard/giftcard',
    ],

    // 拍卖活动:
    'auction' => [
		'index' => '/pagesA/auction/auction',
        'detail' => '/pagesA/auction/detail/detail?act_id=',
    ],

    // 超值礼包:
    'package' => [
		//'index' => '/pagesA/package/package',
        'index' => '',
    ],

    // 拼团:
    'team' => [
		'index' => '/pagesA/team/team',
    ],

    // 砍价:
    'bargain' => [
        'index' => '/pagesA/bargain/bargain',
    ],
];
