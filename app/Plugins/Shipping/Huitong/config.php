<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    /* 配送方式插件的代码必须和文件名保持一致 */
    'code' => 'huitong',

    'version' => '1.0.0',

    /* 配送方式的描述 */
    'desc' => 'huitong_desc',

    /* 配送方式是否支持货到付款 */
    'cod' => false,

    /* 插件的作者 */
    'author' => 'DscMall TEAM',

    /* 插件作者的官方网站 */
    'website' => 'http://www.ecmoban.com',

    /* 配送接口需要的参数 */
    'configure' => [
        ['name' => 'item_fee', 'value' => 10],  /* 单件商品的配送价格 */
        ['name' => 'base_fee', 'value' => 5],   /* 1000克以内的价格 */
        ['name' => 'step_fee', 'value' => 5],   /* 续重每1000克增加的价格 */
    ],

    /* 模式编辑器 */
    'print_model' => 2,

    /* 打印单背景 */
    'print_bg' => '/images/receipt/dly_huitong.jpg',

    /* 打印快递单标签位置信息 */
    'config_lable' => 't_shop_province,' . $GLOBALS['_LANG']['lable_box']['shop_province'] . ',132,24,279.6,105.7,b_shop_province||,||t_shop_name,' . $GLOBALS['_LANG']['lable_box']['shop_name'] . ',268,29,142.95,133.85,b_shop_name||,||t_shop_address,' . $GLOBALS['_LANG']['lable_box']['shop_address'] . ',346,40,67.3,199.95,b_shop_address||,||t_shop_city,' . $GLOBALS['_LANG']['lable_box']['shop_city'] . ',64,35,223.8,163.95,b_shop_city||,||t_shop_district,' . $GLOBALS['_LANG']['lable_box']['shop_district'] . ',56,35,314.9,164.25,b_shop_district||,||t_pigeon,' . $GLOBALS['_LANG']['lable_box']['pigeon'] . ',21,21,143.1,263.2,b_pigeon||,||t_customer_name,' . $GLOBALS['_LANG']['lable_box']['customer_name'] . ',89,25,488.65,121.05,b_customer_name||,||t_customer_tel,' . $GLOBALS['_LANG']['lable_box']['customer_tel'] . ',136,21,656,110.6,b_customer_tel||,||t_customer_mobel,' . $GLOBALS['_LANG']['lable_box']['customer_mobel'] . ',137,21,655.6,132.8,b_customer_mobel||,||t_customer_province,' . $GLOBALS['_LANG']['lable_box']['customer_province'] . ',115,24,480.2,173.5,b_customer_province||,||t_customer_city,' . $GLOBALS['_LANG']['lable_box']['customer_city'] . ',60,27,609.3,172.5,b_customer_city||,||t_customer_district,' . $GLOBALS['_LANG']['lable_box']['customer_district'] . ',58,28,696.8,173.25,b_customer_district||,||t_customer_post,' . $GLOBALS['_LANG']['lable_box']['customer_post'] . ',93,21,701.1,240.25,b_customer_post||,||',

    /* 是否支持快递鸟打印 */
    'kdniao_print' => true,

    /* 帐号申请方式 */
    'kdniao_account' => 2,

    /* 快递编码 */
    'kdniao_code' => "HTKY",

    /* 快递鸟打印尺寸-宽度 */
    'kdniao_width' => 100,

    /* 快递鸟打印尺寸-高度 */
    'kdniao_height' => 180

];
