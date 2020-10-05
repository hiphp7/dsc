<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Sms/', __DIR__);

return [
    'code' => 'ihuyi', // code
    // 描述对应的语言项
    'description' => 'ihuyi_desc',
    'version' => '1.0', // 版本号
    // 网址
    'website' => 'https://www.ihuyi.com/',
    'sort' => '3', // 默认排序
    // 配置
    'sms_configure' => [
        ['name' => 'sms_ecmoban_user', 'type' => 'text', 'value' => ''],
        ['name' => 'sms_ecmoban_password', 'type' => 'text', 'value' => '', 'encrypt' => true],
    ]
];
