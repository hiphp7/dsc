<?php

namespace App\Api\Foundation\Controllers;

use App\Api\Foundation\Components\ApiResponse;
use App\Http\Controllers\Controller as BaseController;
use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Rules\PhoneNumber;
use App\Services\Common\AreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Class Controller
 * @package App\Api\Foundation\Controllers
 */
class Controller extends BaseController
{
    use ApiResponse;

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * 地区-省份
     *
     * @var int
     */
    protected $province_id = 0;

    /**
     * 地区-城市
     *
     * @var int
     */
    protected $city_id = 0;

    /**
     * 地区-区县
     *
     * @var int
     */
    protected $district_id = 0;

    /**
     * 仓库
     *
     * @var int
     */
    protected $warehouse_id = 0;

    /**
     * 仓库-省份
     *
     * @var int
     */
    protected $area_id = 0;

    /**
     * 仓库-城市
     *
     * @var int
     */
    protected $area_city = 0;

    /**
     * 登录会员ID
     *
     * @var int
     */
    protected $uid = 0;

    protected function initialize()
    {
        if (!isset($GLOBALS['_CFG'])) {
            /* 商城配置信息 */
            $shopConfig = cache('shop_config');
            if (is_null($shopConfig)) {
                $config = app(\App\Services\Common\ConfigService::class)->getConfig();
            } else {
                $config = $shopConfig;
            }

            load_helper([
                'time', 'base', 'common', 'main', 'insert', 'goods', 'article',
                'ecmoban', 'function', 'seller_store', 'scws', 'wholesale', 'passport'
            ]);

            $GLOBALS['_CFG'] = $config;
        }

        if (!isset($GLOBALS['_LANG'])) {
            load_lang(['common', 'js_languages', 'user', 'shopping_flow']);
        }

        $GLOBALS['dsc'] = app(Shop::class);
        $GLOBALS['db'] = app(Mysql::class);
        $GLOBALS['err'] = app(Error::class);

        defined('SESS_ID') or define('SESS_ID', session()->getId());
        if (function_exists('init_users')) {
            $GLOBALS['user'] = init_users();
        }

        /* 登录会员ID */
        $this->uid = $this->authorization();

        $area_cache_name = app(AreaService::class)->getCacheName('area_cookie', $this->uid);

        $area_cookie_list = cache($area_cache_name);
        $area_cookie_list = is_null($area_cookie_list) ? false : $area_cookie_list;

        #需要查询的IP start
        if (!isset($area_cookie_list['province']) || empty($area_cookie_list['province'])) {
            $areaInfo = app(AreaService::class)->selectAreaInfo();

            $this->province_id = $areaInfo['province_id'];
            $this->city_id = $areaInfo['city_id'];
            $this->district_id = $areaInfo['district_id'];

            if ($area_cookie_list === false) {
                $area_cookie_cache = [
                    'province' => $this->province_id,
                    'city_id' => $this->city_id,
                    'district' => $this->district_id,
                    'street' => 0,
                    'street_area' => 0
                ];

                cache()->forever($area_cache_name, $area_cookie_cache);
            }
        } else {
            $this->province_id = $area_cookie_list['province'];
            $this->city_id = $area_cookie_list['city_id'];
            $this->district_id = $area_cookie_list['district'];
        }
        #需要查询的IP end

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_cache_name = app(AreaService::class)->getCacheName('warehouse_cookie', $this->uid);
        $warehouse_cookie_list = cache($warehouse_cache_name);

        if (is_null($warehouse_cookie_list)) {
            $areaOther = [
                'province_id' => $this->province_id,
                'city_id' => $this->city_id
            ];

            $areaInfo = app(AreaService::class)->getAreaInfo($areaOther, $this->uid);

            $this->warehouse_id = $areaInfo['area']['warehouse_id'];
            $this->area_id = $areaInfo['area']['area_id'];
            $this->area_city = $areaInfo['area']['city_id'];
        } else {
            $this->warehouse_id = $warehouse_cookie_list['warehouse_id'];
            $this->area_id = $warehouse_cookie_list['area_id'];
            $this->area_city = $warehouse_cookie_list['area_city'];
        }
        /* End */
    }

    /**
     * @return int
     */
    protected function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $statusCode
     * @return $this
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param array $header 发送的Header信息
     * @return JsonResponse
     */
    protected function succeed($data, array $header = [])
    {
        return $this->response([
            'status' => 'success',
            'data' => $data,
            'time' => time(),
        ])->withHeaders($header);
    }

    /**
     * 返回异常数据到客户端
     * @param $message
     * @return JsonResponse
     */
    protected function failed($message)
    {
        return $this->response([
            'status' => 'failed',
            'errors' => [
                'code' => $this->getStatusCode(),
                'message' => $message,
            ],
            'time' => time(),
        ]);
    }

    /**
     * 返回 Not Found 异常
     * @param string $message
     * @return JsonResponse
     */
    protected function responseNotFound($message = 'Not Found')
    {
        return $this->setStatusCode(404)->failed($message);
    }

    /**
     * 返回 Json 数据格式
     * @param $data
     * @return JsonResponse
     */
    protected function response($data)
    {
        // 客户端设备唯一ID
        $client_hash = request()->header('X-Client-Hash');

        if (is_null($client_hash) || empty($client_hash)) {
            $client_hash = session()->getId();
        }

        return response()->json($data)->withHeaders([
            'X-Client-Hash' => $client_hash
        ]);
    }

    /**
     * 短信验证码校验
     * @param Request $request
     * @return bool
     * @throws ValidationException
     */
    protected function verifySMS(Request $request)
    {
        $this->validate($request, [
            'client' => 'required',
            'mobile' => ['required', new PhoneNumber()],
            'code' => 'required',
        ], [
            'client.required' => '请发送短信验证码',
            'mobile.required' => '请填写手机号码',
            'code.required' => '请填写短信验证码',
        ]);

        $client_id = $request->get('client');
        $mobile = $request->get('mobile');
        $sms_code = $request->get('code');
        $label = $client_id . $mobile;

        // 记录错误次数
        $errorNum = Cache::get($label . 'error', 0);

        // 错误验证码且超过3次，直接返回错误
        if ((Cache::get($label) != $sms_code) || $errorNum > 3) {
            Cache::get($label . 'error', $errorNum + 1);
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return array
     */
    protected function getAction()
    {
        $name = request()->route()->getActionName();
        $actions = explode('\\', $name);
        list($controller, $action) = explode('@', end($actions));
        return [
            'controller' => $controller,
            'action' => $action,
            'script_name' => parse_name(substr($controller, 0, -10)) . '.php',
        ];
    }
}
