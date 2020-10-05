<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    /* 配送方式插件的代码必须和文件名保持一致 */
    'code' => 'deppon',

    'version' => '1.0.0',

    /* 配送方式的描述 */
    'desc' => 'deppon_desc',

    /* 配送方式是否支持货到付款 */
    'cod' => false,

    /* 插件的作者 */
    'author' => 'DscMall TEAM',

    /* 插件作者的官方网站 */
    'website' => 'http://www.ecmoban.com',

    /* 配送接口需要的参数 */
    'configure' => [
        ['name' => 'item_fee', 'value' => 15],  /* 单件商品配送的价格 */
        ['name' => 'base_fee', 'value' => 10],  /* 1000克以内的价格 */
        ['name' => 'step_fee', 'value' => 5],   /* 续重每1000克增加的价格 */
    ],

    /* 模式编辑器 */
    'print_model' => 2,

    /* 打印单背景 */
    'print_bg' => '/images/receipt/dly_deppon.jpg',

    /* 打印快递单标签位置信息 */
    'config_lable' => 't_shop_province,' . $GLOBALS['_LANG']['lable_box']['shop_province'] . ',116,30,296.55,117.2,b_shop_province||,||t_customer_province,' . $GLOBALS['_LANG']['lable_box']['customer_province'] . ',114,32,649.95,114.3,b_customer_province||,||t_shop_address,' . $GLOBALS['_LANG']['lable_box']['shop_address'] . ',260,57,151.75,152.05,b_shop_address||,||t_shop_name,' . $GLOBALS['_LANG']['lable_box']['shop_name'] . ',259,28,152.65,212.4,b_shop_name||,||t_shop_tel,' . $GLOBALS['_LANG']['lable_box']['shop_tel'] . ',131,37,138.65,246.5,b_shop_tel||,||t_customer_post,' . $GLOBALS['_LANG']['lable_box']['customer_post'] . ',104,39,659.2,242.2,b_customer_post||,||t_customer_tel,' . $GLOBALS['_LANG']['lable_box']['customer_tel'] . ',158,22,461.9,241.9,b_customer_tel||,||t_customer_mobel,' . $GLOBALS['_LANG']['lable_box']['customer_mobel'] . ',159,21,463.25,265.4,b_customer_mobel||,||t_customer_name,' . $GLOBALS['_LANG']['lable_box']['customer_name'] . ',109,32,498.9,115.8,b_customer_name||,||t_customer_address,' . $GLOBALS['_LANG']['lable_box']['customer_address'] . ',264,58,499.6,150.1,b_customer_address||,||t_months,' . $GLOBALS['_LANG']['lable_box']['months'] . ',35,23,135.85,392.8,b_months||,||t_day,' . $GLOBALS['_LANG']['lable_box']['day'] . ',24,23,180.1,392.8,b_day||,||',

    /* 是否支持快递鸟打印 */
    'kdniao_print' => true,

    /* 帐号申请方式 */
    'kdniao_account' => 2,

    /* 快递编码 */
    'kdniao_code' => "deppon",

    /* 快递鸟打印尺寸-宽度 */
    'kdniao_width' => 100,

    /* 快递鸟打印尺寸-高度 */
    'kdniao_height' => 150,

];
