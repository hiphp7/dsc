<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Payment', __DIR__);

return [
    // 代码
    'code' => 'cod',

    // 描述对应的语言项
    'desc' => 'cod_desc',

    // 是否支持货到付款
    'is_cod' => '1',

    // 是否支持在线支付
    'is_online' => '0',

    // 支付费用，由配送决定
    'pay_fee' => '0',

    // 作者
    'author' => 'Dscmall Team',

    // 网址
    'website' => 'http://www.ecmoban.com',

    // 版本号
    'version' => '1.0.0',

    // 配置信息
    'config' => []
];
