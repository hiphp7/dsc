<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Sms/', __DIR__);

return [
    'code' => 'huawei', // code
    // 描述对应的语言项
    'description' => 'huawei_desc',
    'version' => '1.0', // 版本号
    // 网址
    'website' => 'https://www.huawei.com/',
    'sort' => '4', // 默认排序
    // 配置
    'sms_configure' => [
        ['name' => 'huawei_sms_key', 'type' => 'text', 'value' => ''],
        ['name' => 'huawei_sms_secret', 'type' => 'text', 'value' => '', 'encrypt' => true],
    ]
];
