<?php

namespace App\Dsctrait;

use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Libraries\Template;
use App\Models\Region;
use App\Models\RegionWarehouse;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\AreaService;
use App\Services\User\UserCommonService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait IniTrait
{
    protected $dsc;
    protected $db;
    protected $err;
    protected $sess;
    protected $smarty;

    protected $province_id = 0;
    protected $city_id = 0;
    protected $district_id = 0;
    protected $street_id = 0;
    protected $street_list = 0;

    /* 仓库地区信息 */
    protected $warehouse_id = 0;
    protected $area_id = 0;
    protected $area_city = 0;
    protected $config;
    protected $region_name;

    protected function initialize()
    {
        /* 安装 start */
        $lockfile = Storage::disk('local')->exists('seeder/install.lock.php');
        if (!$lockfile) {
            return redirect(route('install'));
        }
        /* 安装 end */

        $shopConfig = cache('shop_config');
        $shopConfig = !is_null($shopConfig) ? $shopConfig : false;
        if ($shopConfig === false) {
            $this->config = app(\App\Services\Common\ConfigService::class)->getConfig();
        } else {
            $this->config = $shopConfig;
        }

        $shop = new Shop();
        $mysql = new Mysql();
        $template = new Template();
        $error = new Error();

        $php_self = Str::snake(basename($this->getCurrentControllerName(), 'Controller'));
        defined('PHP_SELF') or define('PHP_SELF', $php_self . '.php');

        $_GET = request()->query() + request()->route()->parameters();
        $_POST = request()->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

        $load_helper = [
            'time', 'base', 'common', 'main', 'insert', 'goods', 'article',
            'ecmoban', 'function', 'seller_store', 'scws', 'wholesale'
        ];
        load_helper($load_helper);

        /* 对用户传入的变量进行转义操作。*/
        if (!empty($_GET)) {
            $_GET = addslashes_deep($_GET);
        }
        if (!empty($_POST)) {
            $_POST = addslashes_deep($_POST);
        }

        $_REQUEST = addslashes_deep($_REQUEST);

        /* 创建 SHOP 对象 */
        $this->dsc = $GLOBALS['dsc'] = $shop;
        define('DATA_DIR', $this->dsc->data_dir());
        define('IMAGE_DIR', $this->dsc->image_dir());

        /* 初始化数据库类 */
        $this->db = $GLOBALS['db'] = $mysql;

        /* 创建错误处理对象 */
        $this->err = $GLOBALS['err'] = $error;

        /* 载入系统参数 */
        $GLOBALS['_CFG'] = $this->config;
        config(['shop' => $GLOBALS['_CFG']]);
        if (config('shop.rewrite') > 0) {
            request()->replace($_REQUEST);
        }

        /* 载入语言文件 */
        load_lang(['common', 'js_languages', basename(PHP_SELF, '.php')]);

        if (config('shop.shop_closed') == 1) {
            /* 商店关闭了，输出关闭的消息 */
            return view('closed')->with('close_comment', config('shop.close_comment'));
        }

        if (is_spider()) {
            /* 如果是蜘蛛的访问，那么默认为访客方式，并且不记录到日志中 */
            if (!defined('INIT_NO_USERS')) {
                define('INIT_NO_USERS', true);
                /* 整合UC后，如果是蜘蛛访问，初始化UC需要的常量 */
                if (config('shop.integrate_code') == 'ucenter') {
                    $GLOBALS['user'] = &init_users();
                }
            }

            /* 清空 */
            session([]);

            /* 赋值 */
            session([
                'user_id' => 0,
                'user_name' => '',
                'email' => '',
                'user_rank' => 0,
                'discount' => 1.00
            ]);
        }

        if (!defined('INIT_NO_USERS')) {
            /* 初始化session */
            app(SessionRepository::class)->sessionRepy();

            define('SESS_ID', app(SessionRepository::class)->getSessionId());
        }

        if (request()->server('PHP_SELF')) {
            $phpSelf = htmlspecialchars(request()->server('PHP_SELF'));
            request()->server('PHP_SELF', $phpSelf);
        }

        $app_mode = config('app.mode');
        if (($app_mode == 0 && is_mobile_device()) || $app_mode == 2) {
            $GLOBALS['_CFG']['template'] .= '/mobile';
        }

        define('__ROOT__', url('/') . '/');
        define('__PUBLIC__', asset('/assets'));
        define('__TPL__', asset('/themes/' . config('shop.template')));
        define('__STORAGE__', __ROOT__ . "storage");

        header('Cache-control: private');
        header('Content-type: text/html; charset=' . EC_CHARSET);

        /* 创建 Smarty 对象。*/
        $this->smarty = $GLOBALS['smarty'] = $template;

        $this->smarty->cache_lifetime = config('shop.cache_time');
        $this->smarty->template_dir = resource_path('views/themes/' . config('shop.template'));
        $this->smarty->cache_dir = storage_path('framework/temp/caches');
        $this->smarty->compile_dir = storage_path('framework/temp/compiled');

        if (config('app.debug')) {
            $this->smarty->direct_output = true;
            $this->smarty->force_compile = true;
        } else {
            $this->smarty->direct_output = false;
            $this->smarty->force_compile = false;
        }

        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang'] ?? 'zh_cn');
        $this->smarty->assign('ecs_charset', EC_CHARSET);
        if (!empty($GLOBALS['_CFG']['stylename'])) {
            $this->smarty->assign('ecs_css_path', 'themes/' . config('shop.template') . '/style_' . config('shop.stylename') . '.css');
        } else {
            $this->smarty->assign('ecs_css_path', 'themes/' . config('shop.template') . '/style.css');
        }

        $this->smarty->assign('ecs_css_suggest', 'themes/' . config('shop.template') . '/suggest.css'); //模糊搜索 buy guan

        /*  @author-bylu IM在线客服(用于判断平台是否开启IM在线客服功能) start */
        $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
        $this->smarty->assign('kf_im_switch', $kf_im_switch);
        /*  @author-bylu  end */

        /* 会员信息 */
        $GLOBALS['user'] = init_users();
        $lang_common = trans('common');
        if (!session()->has('user_id')) {
            /* 获取投放站点的名称 */
            $site_name = isset($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes($lang_common['self_site']);
            $from_ad = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

            session([
                'from_ad' => $from_ad, // 用户点击的广告ID
                'referer' => stripslashes($site_name) // 用户来源
            ]);

            unset($site_name);

            if (!defined('INGORE_VISIT_STATS')) {
                visit_stats();
            }
        }

        if (session()->has('user_id') && empty(session('user_id'))) {
            if ($GLOBALS['user']->get_cookie()) {
                /* 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券 */
                if (session('user_id') > 0) {
                    app(UserCommonService::class)->updateUserInfo();
                }
            } else {
                session([
                    'user_id' => 0,
                    'user_name' => '',
                    'email' => '',
                    'user_rank' => 0,
                    'discount' => 1
                ]);

                if (!session()->has('login_fail')) {
                    session(['login_fail' => 0]);
                }
            }
        }

        /* 设置推荐会员 */
        $uid = request()->input('u', 0);
        if ($uid > 0) {
            app(CommonRepository::class)->setUserAffiliate($uid);
            app(CommonRepository::class)->setDrpAffiliate($uid);
        }

        /* session 不存在，检查cookie */
        $userEcs = request()->cookie('ECS');
        if (!empty($userEcs['user_id']) && !empty($userEcs['password'])) {
            // 找到了cookie, 验证cookie信息
            $row = Users::where('user_id', intval($userEcs['user_id']))
                ->where('password', addslashes($userEcs['password']))
                ->first();
            $row = $row ? $row->toArray() : [];

            if (!$row) {
                // 没有找到这个记录
                $list = [
                    'ECS[user_id]',
                    'ECS[password]'
                ];

                app(SessionRepository::class)->deleteCookie($list);
            } else {
                session([
                    'user_id' => $row['user_id'] ?? 0,
                    'user_name' => $row['user_name'] ?? ''
                ]);

                app(UserCommonService::class)->updateUserInfo();
            }
        } else {
            if (session()->has('user_id') && session('user_id') > 0) {
                $userCount = Users::where('user_id', intval(session('user_id')))->count();

                if ($userCount == 0) {
                    session([
                        'user_id' => 0,
                        'user_name' => '',
                        'email' => '',
                        'user_rank' => 0,
                        'discount' => 1
                    ]);

                    if (!session()->has('login_fail')) {
                        session(['login_fail' => 0]);
                    }

                    $GLOBALS['user']->logout();
                }
            }
        }

        if (isset($this->smarty)) {
            $filename = basename(PHP_SELF, '.php');

            if (strpos($filename, 'user_') !== false) {
                $filename = 'user';
            }

            $file_languages = (isset($GLOBALS['_LANG']['js_languages'][$filename]) && is_array($GLOBALS['_LANG']['js_languages'][$filename])) ? $GLOBALS['_LANG']['js_languages'][$filename] : [];
            $byCode = base64_decode('c2hvcC5wb3dlcmJ5');
            $keyCode = base64_decode('cG93ZXJieQ==');

            if (isset($GLOBALS['_LANG']['js_languages'])) {
                $merge_js_languages = array_merge($GLOBALS['_LANG']['js_languages'], $file_languages, [$keyCode => config($byCode)]);
            } elseif ($file_languages) {
                $merge_js_languages = $file_languages;
            } else {
                $merge_js_languages = [];
            }

            $json_languages = json_encode($merge_js_languages);

            $this->smarty->assign('json_languages', $json_languages);
            // 卖场
            $this->smarty->assign('rs_enabled', $GLOBALS['_CFG']['region_store_enabled'] ?? 0);
        }

        $this->area();

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $areaInfo = app(AreaService::class)->getAreaInfo();

        $this->warehouse_id = $areaInfo['area']['warehouse_id'];
        $this->area_id = $areaInfo['area']['area_id'];
        $this->area_city = $areaInfo['area']['city_id'];
        /* End */
    }

    protected function area()
    {
        $area_cache_name = app(AreaService::class)->getCacheName('area_cookie');

        $area_cookie_list = cache($area_cache_name);
        $area_cookie_list = !is_null($area_cookie_list) ? $area_cookie_list : false;

        #需要查询的IP start
        $province_id = 0;
        $city_id = 0;
        $district_id = 0;
        if (!isset($area_cookie_list['province']) || empty($area_cookie_list['province'])) {
            $areaInfo = app(AreaService::class)->selectAreaInfo();

            $province_id = $areaInfo['province_id'];
            $city_id = $areaInfo['city_id'];
            $district_id = $areaInfo['district_id'];

            /* 初始化地区cookie */
            $area_cookie_list['province'] = 0;
            $area_cookie_list['city'] = 0;
            $area_cookie_list['district'] = 0;
        }
        #需要查询的IP end

        $type_province = request()->cookie('type_province');
        $type_city = request()->cookie('type_city');
        $type_district = request()->cookie('type_district');

        //判断地区关联是否选择完毕 start
        if ($type_city) {
            $city_district_list = get_isHas_area($type_city);
            if (!$city_district_list) {
                cookie()->queue('type_district', 0, 30 * 24 * 60);
            }
        } else {
            $city_district_list = [];
        }

        if ($type_province) {
            $provinceT_list = get_isHas_area($type_province);

            if ($provinceT_list) {
                $cityT_list = get_isHas_area($type_city, 1);

                if ($city_district_list) {
                    $districtT_list = get_isHas_area($type_district, 1);

                    if (isset($cityT_list['parent_id']) && $districtT_list['parent_id'] && $cityT_list['parent_id'] == $type_province && $type_city == $districtT_list['parent_id']) {
                        $area_cookie_list['province'] = $type_province;
                        if ($type_city > 0) {
                            $area_cookie_list['city'] = $type_city;
                        }

                        if ($type_district > 0) {
                            $area_cookie_list['district'] = $type_district;
                        }
                    }
                } else {
                    if ($cityT_list && $cityT_list['parent_id'] == $type_province) {
                        $area_cookie_list['province'] = $type_province;
                        if ($type_city > 0) {
                            $area_cookie_list['city'] = $type_city;
                        }

                        if ($type_district > 0) {
                            $area_cookie_list['district'] = $type_district;
                        }
                    }
                }
            }
        } else {
            $area_cookie_list = app(AreaService::class)->areaCookie();
            $area_cookie_list['city'] = $area_cookie_list['city_id'] ?? 0;
        }

        //判断地区关联是否选择完毕 end
        $this->province_id = isset($area_cookie_list['province']) && !empty($area_cookie_list['province']) ? $area_cookie_list['province'] : $province_id;
        $this->city_id = isset($area_cookie_list['city']) && !empty($area_cookie_list['city']) ? $area_cookie_list['city'] : $city_id;
        $this->district_id = isset($area_cookie_list['district']) && !empty($area_cookie_list['district']) ? $area_cookie_list['district'] : $district_id;

        //判断仓库是否存在该地区
        if ($this->province_id) {
            $warehouse_province = RegionWarehouse::select('region_id', 'region_name')->where('regionId', $this->province_id)->first();
            $warehouse_province = $warehouse_province ? $warehouse_province->toArray() : [];

            if (!$warehouse_province) {
                $sellerInfo = get_shop_info_content();

                $this->province_id = $sellerInfo['province'] ?? 0;
                $this->city_id = $sellerInfo['city'] ?? 0;
                $this->district_id = $sellerInfo['district'] ?? 0;
            }
        }

        $street_list = 0;
        $street_id = 0;
        if (!isset($area_cookie_list['street']) && !isset($area_cookie_list['street_area'])) {
            $street_info = Region::select('region_id')->where('parent_id', $this->district_id)->get();
            $street_info = $street_info ? $street_info->toArray() : [];
            $street_info = $street_info ? collect($street_info)->pluck('region_id')->all() : [];

            if ($street_info) {
                $street_id = $street_info[0];
                $street_list = implode(",", $street_info);
            }
        }

        $this->street_id = isset($area_cookie_list['street']) && !empty($area_cookie_list['street']) ? $area_cookie_list['street'] : $street_id;
        $this->street_list = isset($area_cookie_list['street_area']) && !empty($area_cookie_list['street_area']) ? $area_cookie_list['street_area'] : $street_list;

        $area_cookie_cache = cache($area_cache_name);
        $area_cookie_cache = !is_null($area_cookie_cache) ? $area_cookie_cache : false;
        if ($area_cookie_cache === false) {
            $area_cookie_cache = [
                'province' => $this->province_id,
                'city_id' => $this->city_id,
                'district' => $this->district_id,
                'street' => $street_id,
                'street_area' => $this->street_list
            ];

            cache()->forever($area_cache_name, $area_cookie_cache);
        }

        $this->region_name = Region::where('region_id', $this->city_id)->value('region_name');
        $GLOBALS['smarty']->assign('region_name', $this->region_name);

        $GLOBALS['smarty']->assign('area_phpName', 'get_ajax_content.php');
        $GLOBALS['smarty']->assign('province', $this->province_id);

        $raid_cache_name = app(AreaService::class)->getCacheName('raid_cookie');
        $area_cookie_cache = cache($raid_cache_name);
        $area_cookie_cache = !is_null($area_cookie_cache) ? $area_cookie_cache : 0;
        $GLOBALS['smarty']->assign('ra_id', $area_cookie_cache);

        $GLOBALS['smarty']->assign('city_top', $this->city_id);
        $GLOBALS['smarty']->assign('district_top', $this->district_id);

        $selectLocate = 0;
        if (isset($area_cookie_list['province'])) {
            $selectLocate = 1;
        }
        $GLOBALS['smarty']->assign('selectLocate', $selectLocate);

        $basic_info = SellerShopinfo::where('ru_id', 0)->first();
        $basic_info = $basic_info ? $basic_info->toArray() : [];

        $chat = app(DscRepository::class)->chatQq($basic_info);
        $basic_info['kf_ww'] = $chat['kf_ww'];
        $basic_info['kf_qq'] = $chat['kf_qq'];

        $GLOBALS['smarty']->assign('basic_info', $basic_info);

        $user_id = session()->has('user_id') ? intval(session('user_id')) : 0;
        $GLOBALS['smarty']->assign('user_id', $user_id);

        /* 跨域 */
        $this->smarty->assign('is_jsonp', 0);

        $asset = config('app.url');
        $asset = rtrim($asset, '/') . "/";
        $this->smarty->assign('asset', $asset);
    }
}
