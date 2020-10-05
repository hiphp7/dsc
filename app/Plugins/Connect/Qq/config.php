<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Connect/', __DIR__);

return [
    // 插件名称
    'name' => 'QQ',

    // 插件code
    'type' => 'qq',

    // 插件的描述
    'desc' => 'qq_desc',

    // 插件的作者
    'author' => 'Dscmall Team',

    // 作者QQ
    'qq' => '800007167',

    // 作者邮箱
    'email' => 'support@dscmall.cn',

    // 申请网址
    'website' => 'http://connect.qq.com/',

    // 版本号
    'version' => '1.0',

    // 更新日期
    'date' => '2018-07-03',

    //配置信息
    'config' => [
        ['type' => 'text', 'name' => 'app_id', 'value' => ''],
        ['type' => 'text', 'name' => 'app_key', 'value' => ''],
    ]
];
