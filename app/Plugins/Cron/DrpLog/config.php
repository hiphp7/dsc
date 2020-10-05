<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Cron/', __DIR__);

return [
    'code' => 'drp_log',
    'desc' => 'drp_log_desc',
    'author' => 'Dscmall Team',
    'website' => 'http://www.dscmall.cn',
    'version' => '1.0.0',
    'config' => [
        ['name' => 'auto_drp_log_count', 'type' => 'select', 'value' => '50']
    ]
];
