<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Payment', __DIR__);

return [
    // 代码
    'code' => 'alipay',

    // 描述对应的语言项
    'desc' => 'alipay_desc',

    // 是否支持货到付款
    'is_cod' => '0',

    // 是否支持在线支付
    'is_online' => '1',

    // 作者
    'author' => 'Dscmall Team',

    // 网址
    'website' => 'http://www.alipay.com',

    // 版本号
    'version' => '1.0.2',

    // 配置信息
    'config' => [
        ['name' => 'alipay_account', 'type' => 'text', 'value' => ''],
        ['name' => 'alipay_key', 'type' => 'text', 'value' => '', 'encrypt' => true],
        ['name' => 'alipay_partner', 'type' => 'text', 'value' => ''],
        ['name' => 'alipay_pay_method', 'type' => 'select', 'value' => ''],
        ['name' => 'use_sandbox', 'type' => 'select', 'value' => ''],
        ['name' => 'app_id', 'type' => 'text', 'value' => ''],
        ['name' => 'sign_type', 'type' => 'select', 'value' => ''],
        ['name' => 'ali_public_key', 'type' => 'textarea', 'value' => ''],
        ['name' => 'rsa_private_key', 'type' => 'textarea', 'value' => ''],
    ]
];
