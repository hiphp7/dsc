<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Sms/', __DIR__);

return [
    'code' => 'aliyun', // code
    // 描述对应的语言项
    'description' => 'aliyun_desc',
    'version' => '1.0', // 版本号
    // 网址
    'website' => 'https://www.aliyun.com/',
    'sort' => '1', // 默认排序
    // 配置
    'sms_configure' => [
        ['name' => 'access_key_id', 'type' => 'text', 'value' => ''],
        ['name' => 'access_key_secret', 'type' => 'text', 'value' => '', 'encrypt' => true],
    ]
];
