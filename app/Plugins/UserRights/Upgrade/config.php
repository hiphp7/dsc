<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('UserRights/', __DIR__);

return [
    'code' => 'upgrade', // code
    // 描述对应的语言项
    'description' => 'upgrade_desc',
    // 默认icon 图标
    'icon' => 'assets/user_rights/img/upgrade.png',// 默认icon 图标
    'trigger_point' => 'direct',
    'version' => '1.0', // 版本号
    'sort' => '1', // 默认排序
    'group' => 'invitation', // 用于后台分组展示
    // 权益配置
    'rights_configure' => [
        ['name' => 'send_integral', 'type' => 'text', 'value' => '']
    ]
];
