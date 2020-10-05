<?php

use App\Repositories\Common\DscRepository;

app(DscRepository::class)->pluginsLang('Cron/', __DIR__);

return [
    'code' => 'delivery',
    'desc' => 'delivery_desc',
    'author' => 'Dscmall Team',
    'website' => 'http://www.ecmoban.com',
    'version' => '1.0.0',
    'config' => [
        ['name' => 'auto_delivery_order_count', 'type' => 'select', 'value' => '5']
    ]
];
