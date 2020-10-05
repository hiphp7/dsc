<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Payment', __DIR__);

return [
    // 代码
    'code' => 'chunsejinrong',

    // 描述对应的语言项
    'desc' => 'chunsejinrong_desc',

    // 是否支持货到付款
    'is_cod' => '0',

    // 是否支持在线支付
    'is_online' => '1',

    // 作者
    'author' => 'Dscmall Team',

    // 网址
    'website' => 'http://www.ecmoban.com',

    // 版本号
    'version' => '1.0.0',

    // 配置信息
    'config' => []
];
