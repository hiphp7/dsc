<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Cron/', __DIR__);

return [
    'code' => 'sms',
    'desc' => 'sms_desc',
    'author' => 'Dscmall Team',
    'website' => 'http://www.ecmoban.com',
    'version' => '1.0.0',
    'config' => [
        ['name' => 'auto_sms_count', 'type' => 'select', 'value' => '10']
    ]
];
