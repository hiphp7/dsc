<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Shipping', __DIR__);

return [
    // 配送方式插件的代码必须和文件名保持一致
    'code' => 'fpd',

    'version' => '1.0.0',

    // 配送方式的描述
    'desc' => 'fpd_desc',

    // 配送方式是否支持货到付款
    'cod' => false,

    // 插件的作者
    'author' => 'Dscmall Team',

    // 插件作者的官方网站
    'website' => 'http://www.dscmall.cn',

    // 配送接口需要的参数
    'configure' => [],

    // 模式编辑器
    'print_model' => 2,

    // 打印单背景
    'print_bg' => '',

    // 打印快递单标签位置信息
    'config_lable' => '',

];
