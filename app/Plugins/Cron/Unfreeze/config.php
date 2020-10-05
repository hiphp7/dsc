<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Cron/', __DIR__);

return [
    'code' => 'unfreeze',
    'desc' => 'unfreeze_desc',
    'author' => 'Dscmall Team',
    'website' => 'http://www.ecmoban.com',
    'version' => '1.0.0',
    'config' => [
        ['name' => 'auto_unfreeze_count', 'type' => 'select', 'value' => '5']
    ]
];
