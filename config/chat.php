<?php

return [
    'root_path' => env('APP_URL', ''),  //  项目根目录，需写全http 或https  例http://localhost/dsc/（必填）
    'listen_route' => parse_url(env('APP_URL', ''), PHP_URL_HOST),   // 监听地址 （必填， 本机域名）
    'listen_ip' => '',   // 监听ip （选填， 不填则默认为本机）
    'server_port' => '2347',   // 监听端口号（必填）  例  2347
    'port' => '2347',   // 客户端口号（必填）  例  2347
    'local_cert' => '/usr/local/nginx/conf/ssl/fullchain.cer', // 也可以是crt文件   https时需要填  http则不填
    'local_pk' => '/usr/local/nginx/conf/ssl/pk.key', // https时需要填  http则不填
    'default_avatar' => 'avatar.png',     //默认头像
    'default_service_avatar' => 'service.png',     //默认客服头像
    'hash_code' => '1jifakp',     //加密字符串  用于加密登录密码
];
