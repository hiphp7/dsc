<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;
use App\Models\Category;
use App\Models\GiftGardType;
use App\Models\Region;
use App\Models\UserGiftGard;
use App\Services\Activity\GiftGardService;
use App\Services\Article\ArticleCommonService;
use App\Services\Category\CategoryService;
use App\Services\Common\AreaService;
use App\Services\User\UserAddressService;

/**
 * 礼品卡
 */
class GiftGardController extends InitController
{
    protected $areaService;
    protected $giftGardService;
    protected $categoryService;
    protected $config;
    protected $articleCommonService;
    protected $userAddressService;

    public function __construct(
        AreaService $areaService,
        GiftGardService $giftGardService,
        CategoryService $categoryService,
        ArticleCommonService $articleCommonService,
        UserAddressService $userAddressService
    )
    {
        $this->areaService = $areaService;
        $this->giftGardService = $giftGardService;
        $this->categoryService = $categoryService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->userAddressService = $userAddressService;
    }

    public function index()
    {
        /* ------------------------------------------------------ */
        //-- act 操作项的初始化
        /* ------------------------------------------------------ */

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $user_id = session('user_id', 0);

        $act = addslashes(request()->input('act', ''));
        if (empty($act)) {
            if (session('gift_sn')) {
                $act = 'list';
            } else {
                $act = 'gift_login';
            }
        }

        if ($act == 'gift_login') {
            assign_template();

            $position = assign_ur_here('gift_gard');
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            $captcha = intval($this->config['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助
            return $this->smarty->display('gift_gard_login.dwt');
        }

        if ($act == 'check_gift') {
            if (!$user_id) {
                return dsc_header("Location: user.php\n");
            }

            $gift_card = addslashes(trim(request()->input('gift_card', '')));
            $gift_pwd = addslashes(trim(request()->input('gift_pwd', '')));
            $captcha_str = addslashes(trim(request()->input('captcha', '')));

            if (request()->exists('captcha')) {
                if (empty($captcha_str)) {
                    return show_message($GLOBALS['_LANG']['cmt_lang']['captcha_not_null'], $GLOBALS['_LANG']['relogin_lnk'], 'javascript:history.go(-1);', 'error');
                }

                if (($captcha_str & CAPTCHA_LOGIN) && (!($captcha_str & CAPTCHA_LOGIN_FAIL) || (($captcha_str & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                    $verify = app(CaptchaVerify::class);
                    $captcha_code = $verify->check($captcha_str, 'captcha_login');

                    if (!$captcha_code) {
                        return show_message($GLOBALS['_LANG']['invalid_captcha'], $GLOBALS['_LANG']['relogin_lnk'], 'javascript:history.go(-1);', 'error');
                    }
                }
            }

            $result = $this->giftGardService->getCheckGiftLogin($gift_card, $gift_pwd);

            if (!empty($result['url'])) {
                return $result['url'];
            }

            if ($result['error'] == 1) {
                return dsc_header("Location: gift_gard.php?act=list\n");
            } else {
                return show_message($GLOBALS['_LANG']['gift_gard_error'], $GLOBALS['_LANG']['relogin_lnk'], 'gift_gard.php', 'error');
            }
        }

        if ($act == 'exit_gift') {
            /* 摧毁cookie */
            cookie()->queue(cookie()->forget('gift_sn'));

            session([
                'gift_sn' => ''
            ]);

            return redirect("/");
        }

        /* ------------------------------------------------------ */
        //-- 礼品卡商品列表
        /* ------------------------------------------------------ */
        if ($act == 'list') {

            /* 初始化分页信息 */
            $page = (int)request()->input('page', 1);
            $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10;
            $gift_id = session('gift_id') ? intval(session('gift_id')) : 0;
            $gift_sn = session('gift_sn') ? '' : addslashes(session('gift_sn'));

            /* 排序、显示方式以及类型 */
            $cat_id = (int)request()->input('cat_id', 0);

            $default_display_type = $this->config['show_order_type'] == '0' ? 'list' : ($this->config['show_order_type'] == '1' ? 'grid' : 'text');
            $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
            $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'gift_id' : 'gift_id';

            $sort = $default_sort_order_type;
            if (request()->has('sort')) {
                $get_sort = addslashes(request()->input('sort'));
                if (in_array(trim(strtolower($get_sort)), ['gift_gard_id'])) {
                    $sort = $get_sort;
                }
            }

            $order = $default_sort_order_method;
            if (request()->has('order')) {
                $get_order = addslashes(request()->input('order'));
                if (in_array(trim(strtoupper($get_order)), ['ASC', 'DESC'])) {
                    $order = $get_order;
                }
            }

            $ecsCookie = request()->cookie('ECS');
            $display = isset($ecsCookie['display']) ? $ecsCookie['display'] : $default_display_type;
            if (request()->has('display')) {
                $get_display = addslashes(request()->input('display'));
                if (in_array(trim(strtolower($get_display)), ['list', 'grid', 'text'])) {
                    $display = $get_display;
                }
            }

            $display = in_array($display, ['list', 'grid', 'text']) ? $display : 'text';


            /* 页面的缓存ID */
            $cache_id = sprintf('%X', crc32($gift_id . '-' . $gift_sn . '-' . $cat_id . '-' . $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . $display . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . session('user_rank') . '-' .
                $this->config['lang']));
            $content = cache()->remember('gift_gard_list.dwt.' . $cache_id, $this->config['cache_time'], function () use ($gift_id, $gift_sn, $cat_id, $warehouse_id, $area_id, $area_city, $page, $size, $sort, $order) {
                /* 如果页面没有被缓存则重新获取页面的内容 */
                $cat = Category::catInfo($cat_id)->first();
                $cat = $cat ? $cat->toArray() : [];

                if (!empty($cat)) {
                    $this->smarty->assign('keywords', htmlspecialchars($cat['keywords']));
                    $this->smarty->assign('description', htmlspecialchars($cat['cat_desc']));
                }

                assign_template();

                $position = assign_ur_here('gift_gard');
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助
                $history_goods = get_history_goods(0, $warehouse_id, $area_id, $area_city);
                $this->smarty->assign('history_goods', $history_goods);                                   // 商品浏览历史


                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('country_list', get_regions());
                $this->smarty->assign('shop_country', $this->config['shop_country']);
                $this->smarty->assign('shop_province_list', get_regions(1, $this->config['shop_country']));

                $count = $this->giftGardService->getGiftGoodsCount();
                $max_page = ($count > 0) ? ceil($count / $size) : 1;
                if ($page > $max_page) {
                    $page = $max_page;
                }

                $goodslist = $this->giftGardService->giftGetGoods($warehouse_id, $area_id, $area_city, $size, $page);

                //查询卡内金额

                $gift = UserGiftGard::select('gift_id')->where('gift_sn', $gift_sn)->first();

                $gift = $gift ? $gift->toArray() : [];

                if ($gift) {
                    $gift_menory = GiftGardType::select('gift_menory')->where('gift_id', $gift['gift_id'])->first();

                    $gift_menory = $gift_menory ? $gift_menory->toArray() : [];
                }

                $gift_menory['gift_menory'] = isset($gift_menory['gift_menory']) ? $gift_menory['gift_menory'] : 0;


                $this->smarty->assign('gift_menory', $gift_menory['gift_menory']);
                $this->smarty->assign('gift_sn', session('gift_sn'));
                $this->smarty->assign('goods_list', $goodslist);
                $this->smarty->assign('category', $cat_id);

                assign_pager('gift_gard', $gift_id, $count, $size, $sort, $order, $page, '', ''); // 分页
                assign_dynamic('gift_gard_list'); // 动态内容

                return $this->smarty->display('gift_gard_list.dwt');
            });

            return $content;

        }

        /* ------------------------------------------------------ */
        //-- 礼品卡详情
        /* ------------------------------------------------------ */
        elseif ($act == 'take_view') {
            $goods_id = (int)request()->input('id', 0);
            $gift_sn = session('gift_sn') ? addslashes(session('gift_sn')) : '';

            //模板缓存
            $cache_id = sprintf('%X', crc32($gift_sn . '-' . $goods_id . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
            $content = cache()->remember('take_view.dwt.' . $cache_id, $this->config['cache_time'], function () use ($gift_sn, $goods_id) {
                if ($gift_sn) {
                    $pwd = UserGiftGard::where('gift_sn', $gift_sn)->where('is_delete', 1)->first();

                    $pwd = $pwd ? $pwd->toArray() : [];

                    if ($pwd && $this->giftGardService->getCheckGiftLogin($gift_sn, $pwd['gift_password'])) {

                        session([
                            'gift_sn' => ''
                        ]);

                        return dsc_header("Location: gift_gard.php?act=gift_login\n");
                    }
                }


                if (empty($goods_id)) {
                    return dsc_header("Location: gift_gard.php?act=list\n");
                }

                load_helper('transaction');

                assign_template();

                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('country_list', get_regions());
                $this->smarty->assign('shop_country', $this->config['shop_country']);
                $this->smarty->assign('shop_province_list', get_regions(1, $this->config['shop_country']));


                $this->smarty->assign('goods_id', $goods_id);

                $position = assign_ur_here('gift_gard');
                $this->smarty->assign('page_title', $position['title']);    // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助

                return $this->smarty->display('take_view.dwt');
            });

            return $content;
        }

        /* ------------------------------------------------------ */
        //-- 礼品卡领取
        /* ------------------------------------------------------ */
        elseif ($act == 'check_take') {
            $goods_id = (int)request()->input('goods_id', 0);
            $gift_sn = session('gift_sn') ? addslashes(session('gift_sn')) : '';

            if ($gift_sn) {
                $pwd = UserGiftGard::where('gift_sn', $gift_sn)->where('is_delete', 1)->first();

                $pwd = $pwd ? $pwd->toArray() : [];

                if ($pwd && !$this->giftGardService->getCheckGiftLogin(session('gift_sn'), $pwd['gift_password'])) {

                    session([
                        'gift_sn' => ''
                    ]);

                    return show_message($GLOBALS['_LANG']['gift_gard_used'], $GLOBALS['_LANG']['gift_gard_login'], 'gift_gard.php', 'error');
                }
            } else {
                return show_message($GLOBALS['_LANG']['gift_gard_overdue'], $GLOBALS['_LANG']['back_Last'], 'gift_gard.php', 'error');
            }

            if (empty($goods_id)) {
                return dsc_header("Location: gift_gard.php?act=list\n");
            }

            $user_time = gmtime();
            $country = (int)request()->input('country', 0);
            $country_name = Region::where('region_id', $country)->value('region_name');

            $province = (int)request()->input('province', 0);
            $province_name = Region::where('region_id', $province)->value('region_name');

            $city = (int)request()->input('city', 0);
            $city_name = Region::where('region_id', $city)->value('region_name');

            $district = (int)request()->input('district', 0);
            $district_name = Region::where('region_id', $district)->value('region_name');

            $street = (int)request()->input('street', 0);
            $street_name = Region::where('region_id', $street)->value('region_name');

            $desc_address = addslashes(trim(request()->input('address', '')));
            $consignee = addslashes(trim(request()->input('consignee', '')));
            $mobile = addslashes(trim(request()->input('mobile', '')));
            $shipping_time = addslashes(trim(request()->input('shipping_time', '')));

            $address = "[" . $country_name . ' ' . $province_name . ' ' . $city_name . ' ' . $district_name . ' ' . ' ' . $street_name . '] ' . $desc_address;

            if (empty($country_name) || empty($province_name) || empty($city_name) || empty($district_name) || empty($desc_address) || empty($consignee) || empty($mobile)) {
                return show_message($GLOBALS['_LANG']['delivery_Prompt'], $GLOBALS['_LANG']['delivery_again'], 'gift_gard.php', 'error');
            }

            $gardOther = [
                'user_id' => $user_id,
                'goods_id' => $goods_id,
                'user_time' => $user_time,
                'address' => $address,
                'consignee_name' => $consignee,
                'mobile' => $mobile,
                'shipping_time' => $shipping_time,
                'status' => 1,
            ];

            $res = UserGiftGard::where('gift_sn', $gift_sn)->update($gardOther);

            if ($res) {
                session([
                    'gift_sn' => ''
                ]);
                return show_message($GLOBALS['_LANG']['delivery_Success'], $GLOBALS['_LANG']['my_delivery'], 'user.php?act=take_list', 'success');
            } else {
                return show_message($GLOBALS['_LANG']['delivery_fail'], $GLOBALS['_LANG']['delivery_again'], 'gift_gard.php', 'error');
            }
        }

        /* ------------------------------------------------------ */
        //-- 结算页面收货地址编辑
        /* ------------------------------------------------------ */
        elseif ($act == 'edit_Consignee') {
            $address_id = (int)request()->input('address_id', 0);
            $goods_id = (int)request()->input('goodsId', 0);

            $consignee = $this->userAddressService->getUpdateFlowConsignee($address_id, $user_id);

            if ($address_id == 0) {
                $consignee['country'] = 1;
                $consignee['province'] = 0;
                $consignee['city'] = 0;
                $consignee['district'] = 0;
            }

            $this->smarty->assign('consignee', $consignee);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            $this->smarty->assign('please_select', lang('common.please_select'));

            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);
            $this->smarty->assign('goods_id', $goods_id);

            if (session('user_id')) {
                $result['error'] = 0;
                $result['content'] = $this->smarty->fetch("library/consignee_gift.lbi");
            } else {
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['lang_crowd_not_login'];
            }

            return response()->json($result);
        }
    }
}
