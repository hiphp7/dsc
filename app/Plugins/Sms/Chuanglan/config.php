<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Sms/', __DIR__);

return [
    'code' => 'chuanglan', // code
    // 描述对应的语言项
    'description' => 'chuanglan_desc',
    'version' => '1.0', // 版本号
    // 网址
    'website' => 'https://www.253.com/',
    'sort' => '5', // 默认排序
    // 配置
    'sms_configure' => [
        ['name' => 'chuanglan_account', 'type' => 'text', 'value' => ''],
        ['name' => 'chuanglan_password', 'type' => 'text', 'value' => '', 'encrypt' => true],
        ['name' => 'chuanglan_api_url', 'type' => 'text', 'value' => ''],
        ['name' => 'chuanglan_signa', 'type' => 'text', 'value' => ''],
    ]
];
