<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Sms/', __DIR__);

return [
    'code' => 'dscsms', // code
    // 描述对应的语言项
    'description' => 'dscsms_desc',
    'version' => '1.0', // 版本号
    // 网址
    'website' => 'https://www.dscmall.cn/',
    'sort' => '0', // 默认排序
    // 配置
    'sms_configure' => [
        ['name' => 'dsc_appkey', 'type' => 'text', 'value' => ''],
        ['name' => 'dsc_appsecret', 'type' => 'text', 'value' => '', 'encrypt' => true],
    ]
];
