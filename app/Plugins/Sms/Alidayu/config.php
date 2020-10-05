<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Sms/', __DIR__);

return [
    'code' => 'alidayu', // code
    // 描述对应的语言项
    'description' => 'alidayu_desc',
    'version' => '1.0', // 版本号
    // 网址
    'website' => 'https://dayu.aliyun.com/',
    'sort' => '2', // 默认排序
    // 配置
    'sms_configure' => [
        ['name' => 'ali_appkey', 'type' => 'text', 'value' => ''],
        ['name' => 'ali_secretkey', 'type' => 'text', 'value' => '', 'encrypt' => true],
    ]
];
