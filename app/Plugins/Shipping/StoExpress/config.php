<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    /* 配送方式插件的代码必须和文件名保持一致 */
    'code' => 'sto_express',

    'version' => '1.0.0',

    /* 配送方式的描述 */
    'desc' => 'sto_express_desc',

    /* 配送方式是否支持货到付款 */
    'cod' => false,

    /* 插件的作者 */
    'author' => 'ECMOBAN TEAM',

    /* 插件作者的官方网站 */
    'website' => 'http://www.ecmoban.com',

    /* 配送接口需要的参数 */
    'configure' => [
        ['name' => 'item_fee', 'value' => 15],/* 单件商品的配送费用 */
        ['name' => 'base_fee', 'value' => 15], /* 1000克以内的价格   */
        ['name' => 'step_fee', 'value' => 5],  /* 续重每1000克增加的价格 */
    ],

    /* 模式编辑器 */
    'print_model' => 2,

    /* 打印单背景 */
    'print_bg' => '/images/receipt/dly_sto_express.jpg',

    /* 打印快递单标签位置信息 */
    'config_lable' => 't_shop_address,' . $GLOBALS['_LANG']['lable_box']['shop_address'] . ',235,48,131,152,b_shop_address||,||t_shop_name,' . $GLOBALS['_LANG']['lable_box']['shop_name'] . ',237,26,131,200,b_shop_name||,||t_shop_tel,' . $GLOBALS['_LANG']['lable_box']['shop_tel'] . ',96,36,144,257,b_shop_tel||,||t_customer_post,' . $GLOBALS['_LANG']['lable_box']['customer_post'] . ',86,23,578,268,b_customer_post||,||t_customer_address,' . $GLOBALS['_LANG']['lable_box']['customer_address'] . ',232,49,434,149,b_customer_address||,||t_customer_name,' . $GLOBALS['_LANG']['lable_box']['customer_name'] . ',151,27,449,231,b_customer_name||,||t_customer_tel,' . $GLOBALS['_LANG']['lable_box']['customer_tel'] . ',90,32,452,261,b_customer_tel||,||',

    /* 是否支持快递鸟打印 */
    'kdniao_print' => true,

    /* 帐号申请方式 */
    'kdniao_account' => 0,

    /* 快递编码 */
    'kdniao_code' => "STO",

    /* 快递鸟打印尺寸-宽度 */
    'kdniao_width' => 100,

    /* 快递鸟打印尺寸-高度 */
    'kdniao_height' => 150,

];
