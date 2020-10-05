<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    /* 配送方式插件的代码必须和文件名保持一致 */
    'code' => 'post_express',

    'version' => '1.0.0',

    /* 配送方式的描述 */
    'desc' => 'post_express_desc',

    /* 保价比例,如果不支持保价则填入false,支持则还需加入calculate_insure()函数。固定价格直接填入固定数字，按商品总价则在数值后加上%  */
    'insure' => '1%',

    /* 配送方式是否支持货到付款 */
    'cod' => false,

    /* 插件的作者 */
    'author' => 'DscMall TEAM',

    /* 插件作者的官方网站 */
    'website' => 'http://www.ecmoban.com',

    /* 配送接口需要的参数 */
    'configure' => [
        ['name' => 'item_fee', 'value' => 5],
        ['name' => 'base_fee', 'value' => 5],
        ['name' => 'step_fee', 'value' => 2],
        ['name' => 'step_fee1', 'value' => 1]
    ],

    /* 模式编辑器 */
    'print_model' => 2,

    /* 打印单背景 */
    'print_bg' => '',

    /* 打印快递单标签位置信息 */
    'config_lable' => '',

    /* 是否支持快递鸟打印 */
    'kdniao_print' => true,

    /* 帐号申请方式 */
    'kdniao_account' => 0,

    /* 快递编码 */
    'kdniao_code' => "YZPY",

    /* 快递鸟打印尺寸-宽度 */
    'kdniao_width' => 100,

    /* 快递鸟打印尺寸-高度 */
    'kdniao_height' => 180,

];
