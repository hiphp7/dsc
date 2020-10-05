<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('UserRights/', __DIR__);

return [
    'code' => 'drp', // code
    // 描述对应的语言项
    'description' => 'drp_desc',
    // 默认icon 图标
    'icon' => 'assets/user_rights/img/drp.png',// 默认icon 图标
    'trigger_point' => 'direct',
    'version' => '1.0', // 版本号
    'sort' => '2', // 默认排序
    'group' => 'invitation', // 用于后台分组展示
    // 权益配置
    'rights_configure' => [
        ['name' => 'level_1', 'type' => 'text', 'value' => ''],
        ['name' => 'level_2', 'type' => 'text', 'value' => ''],
        ['name' => 'level_3', 'type' => 'text', 'value' => ''],
    ]
];
