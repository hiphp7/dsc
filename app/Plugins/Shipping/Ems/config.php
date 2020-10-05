<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    /* 配送方式插件的代码必须和文件名保持一致 */
    'code' => 'ems',

    'version' => '1.0.0',

    /* 配送方式的描述 */
    'desc' => 'ems_express_desc',

    /* 配送方式是否支持货到付款 */
    'cod' => false,

    /* 插件的作者 */
    'author' => 'DscMall TEAM',

    /* 插件作者的官方网站 */
    'website' => 'http://www.ecmoban.com',

    /* 配送接口需要的参数 */
    'configure' => [
        ['name' => 'item_fee', 'value' => 20],    /* 单件商品的配送费用 */
        ['name' => 'base_fee', 'value' => 20],    /* 1000克以内的价格   */
        ['name' => 'step_fee', 'value' => 15],    /* 续重每1000克增加的价格 */
    ],

    /* 模式编辑器 */
    'print_model' => 2,

    /* 打印单背景 */
    'print_bg' => '/images/receipt/dly_sf_express.jpg',

    /* 打印快递单标签位置信息 */
    'config_lable' => 't_shop_name,' . $GLOBALS['_LANG']['lable_box']['shop_name'] . ',236,32,182,161,b_shop_name||,||t_shop_tel,' . $GLOBALS['_LANG']['lable_box']['shop_tel'] . ',127,21,295,135,b_shop_tel||,||t_shop_address,' . $GLOBALS['_LANG']['lable_box']['shop_address'] . ',296,68,124,190,b_shop_address||,||t_pigeon,' . $GLOBALS['_LANG']['lable_box']['pigeon'] . ',21,21,192,278,b_pigeon||,||t_customer_name,' . $GLOBALS['_LANG']['lable_box']['customer_name'] . ',107,23,494,136,b_customer_name||,||t_customer_tel,' . $GLOBALS['_LANG']['lable_box']['customer_tel'] . ',155,21,639,124,b_customer_tel||,||t_customer_mobel,' . $GLOBALS['_LANG']['lable_box']['customer_mobel'] . ',159,21,639,147,b_customer_mobel||,||t_customer_post,' . $GLOBALS['_LANG']['lable_box']['customer_post'] . ',88,21,680,258,b_customer_post||,||t_year,' . $GLOBALS['_LANG']['lable_box']['year'] . ',37,21,534,379,b_year||,||t_months,' . $GLOBALS['_LANG']['lable_box']['months'] . ',29,21,592,379,b_months||,||t_day,' . $GLOBALS['_LANG']['lable_box']['day'] . ',27,21,642,380,b_day||,||t_order_best_time,' . $GLOBALS['_LANG']['lable_box']['order_best_time'] . ',104,39,688,359,b_order_best_time||,||t_order_postscript,' . $GLOBALS['_LANG']['lable_box']['order_postscript'] . ',305,34,485,402,b_order_postscript||,||t_customer_address,' . $GLOBALS['_LANG']['lable_box']['customer_address'] . ',289,48,503,190,b_customer_address||,||',

    /* 是否支持快递鸟打印 */
    'kdniao_print' => true,

    /* 帐号申请方式 */
    'kdniao_account' => 0,

    /* 快递编码 */
    'kdniao_code' => "EMS",

    /* 快递鸟打印尺寸-宽度 */
    'kdniao_width' => 100,

    /* 快递鸟打印尺寸-高度 */
    'kdniao_height' => 150,

];
