<?php

namespace App\Http\Controllers\Mobile;

use App\Services\Common\ConfigService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class IndexController
 * @package App\Http\Controllers\Mobile
 */
class IndexController extends BaseController
{
    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        $shop_config = app(ConfigService::class)->getConfig();

        config(['shop' => $shop_config]);
    }

    /**
     * 微商城
     * @return Factory|View
     */
    public function mobile()
    {
        return view('mobile');
    }

    /**
     * 微商城授权登录回调
     * @param Request $request
     * @return RedirectResponse|Redirector
     */
    public function callback(Request $request)
    {
        $args = $request->all();

        unset($args[0]);

        if (empty(config('app.mobile_domain'))) {
            return redirect('/mobile/#/callback?' . http_build_query($args, '', '&'));
        } else {
            return redirect('/#/callback?' . http_build_query($args, '', '&'));
        }
    }

    /**
     * 支付同步回调
     * @param Request $request
     * @return RedirectResponse|Redirector
     */
    public function respond(Request $request)
    {
        $args = $request->all();

        unset($args[0]);

        return redirect('/mobile/#/respond?' . http_build_query($args, '', '&'));
    }
}
