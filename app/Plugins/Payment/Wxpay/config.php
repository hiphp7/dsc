<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Payment', __DIR__);

return [
    // 代码
    'code' => 'wxpay',

    // 描述对应的语言项
    'desc' => 'wxpay_desc',

    // 是否支持货到付款
    'is_cod' => '0',

    // 是否支持在线支付
    'is_online' => '1',

    // 作者
    'author' => 'Dscmall Team',

    // 网址
    'website' => 'http://mp.weixin.qq.com/',

    // 版本号
    'version' => '3.0',

    // 配置信息
    'config' => [
        ['name' => 'wxpay_app_appid', 'type' => 'text', 'value' => ''],
        ['name' => 'wxpay_appid', 'type' => 'text', 'value' => ''],
        ['name' => 'wxpay_appsecret', 'type' => 'text', 'value' => '', 'encrypt' => true],
        ['name' => 'wxpay_key', 'type' => 'text', 'value' => ''],
        ['name' => 'wxpay_mchid', 'type' => 'text', 'value' => ''],
        ['name' => 'wxpay_sub_mch_id', 'type' => 'text', 'value' => ''],
        ['name' => 'is_h5', 'type' => 'select', 'value' => ''],
        ['name' => 'sslcert', 'type' => 'textarea', 'value' => ''],
        ['name' => 'sslkey', 'type' => 'textarea', 'value' => ''],
    ]
];
