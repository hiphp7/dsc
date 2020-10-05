<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 生产环境不抛出NOTICE|WARNING
        if (in_array(request()->server('REMOTE_ADDR'), ['127.0.0.1', '::1'])) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
        }

        Carbon::setLocale('zh');

        // 设置 nginx 反向代理模式下 Scheme 参数
        if (substr(config('app.url'), 0, 5) === 'https') {
            URL::forceScheme('https');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
