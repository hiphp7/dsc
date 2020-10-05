<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'forever' => [
            'driver' => 'local',
            'root' => storage_path('framework/cache/forever'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'oss' => [
            'driver' => 'oss',
            'access_id' => env('OSS_ACCESS_ID'), // <Your Aliyun OSS AccessKeyId>
            'access_key' => env('OSS_ACCESS_KEY'), // <Your Aliyun OSS AccessKeySecret>
            'bucket' => env('OSS_BUCKET'), // <OSS bucket name>
            'endpoint' => env('OSS_ENDPOINT'), // <the endpoint of OSS, E.g: oss-cn-hangzhou.aliyuncs.com | custom domain, E.g:img.abc.com> OSS 外网节点或自定义外部域名
            'cdnDomain' => env('OSS_CDN_DOMAIN'), //<CDN domain, cdn域名> 如果isCName为true, getUrl会判断cdnDomain是否设定来决定返回的url，如果cdnDomain未设置，则使用endpoint来生成url，否则使用cdn
            'ssl' => env('OSS_SSL'), // <true|false> true to use 'https://' and false to use 'http://'. default is false,
            'isCName' => env('OSS_IS_CNAME'), // <true|false> 是否使用自定义域名,true: 则Storage.url()会使用自定义的cdn或域名生成文件url， false: 则使用外部节点生成url
            'debug' => env('APP_DEBUG'), // <true|false>
        ],

        'obs' => [
            'driver' => 'obs',
            'key' => env('OBS_ACCESS_ID'), // <Your Huawei OBS AccessKeyId>
            'secret' => env('OBS_ACCESS_KEY'), // <Your Huawei OBS AccessKeySecret>
            'bucket' => env('OBS_BUCKET'), // <OBS bucket name>
            'endpoint' => env('OBS_ENDPOINT'), // <the endpoint of OBS, E.g: (https:// or http://).obs.cn-east-2.myhuaweicloud.com | custom domain, E.g:img.abc.com> OBS 外网节点或自定义外部域名
            'cdn_domain' => env('OBS_CDN_DOMAIN'), //<CDN domain, cdn域名> 如果isCName为true, getUrl会判断cdnDomain是否设定来决定返回的url，如果cdnDomain未设置，则使用endpoint来生成url，否则使用cdn
            'ssl_verify' => env('OBS_SSL_VERIFY'), // <true|false> true to use 'https://' and false to use 'http://'. default is false,
            'debug' => env('APP_DEBUG'), // <true|false>
        ],

    ],

];
