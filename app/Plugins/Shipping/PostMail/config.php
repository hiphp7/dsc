<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    /* 配送方式插件的代码必须和文件名保持一致 */
    'code' => 'post_mail',

    'version' => '1.0.0',

    /* 配送方式的描述 */
    'desc' => 'post_mail_desc',

    /* 配送方式是否支持货到付款 */
    'cod' => false,

    /* 插件的作者 */
    'author' => 'DscMall TEAM',

    /* 插件作者的官方网站 */
    'website' => 'http://www.ecmoban.com',

    /* 配送接口需要的参数 */
    'configure' => [
        ['name' => 'item_fee', 'value' => 4],
        ['name' => 'base_fee', 'value' => 3.5],
        ['name' => 'step_fee', 'value' => 2],
        ['name' => 'step_fee1', 'value' => 2.5],
        ['name' => 'pack_fee', 'value' => 0]
    ],

    /* 模式编辑器 */
    'print_model' => 2,

    /* 打印单背景 */
    'print_bg' => '',

    /* 打印快递单标签位置信息 */
    'config_lable' => '',

    /* 是否支持快递鸟打印 */
    'kdniao_print' => false

];
