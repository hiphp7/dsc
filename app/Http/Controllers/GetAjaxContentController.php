<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Cart;
use App\Models\DeliveryOrder;
use App\Models\Region;
use App\Models\Template;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\CouponsService;
use App\Services\Brand\BrandService;
use App\Services\Category\CategoryGoodsService;
use App\Services\Category\CategoryService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Store\StoreService;
use App\Services\User\UserCommonService;

/**
 * 获取AJAX内容
 */
class GetAjaxContentController extends InitController
{
    protected $areaService;
    protected $brandService;
    protected $categoryService;
    protected $storeService;
    protected $categoryGoodsService;
    protected $goodsWarehouseService;
    protected $dscRepository;
    protected $couponsService;
    protected $config;
    protected $sessionRepository;
    protected $goodsCommonService;
    protected $userCommonService;
    protected $commonService;

    public function __construct(
        AreaService $areaService,
        CouponsService $couponsService,
        BrandService $brandService,
        CategoryService $categoryService,
        StoreService $storeService,
        CategoryGoodsService $categoryGoodsService,
        GoodsWarehouseService $goodsWarehouseService,
        DscRepository $dscRepository,
        SessionRepository $sessionRepository,
        GoodsCommonService $goodsCommonService,
        UserCommonService $userCommonService,
        CommonService $commonService
    )
    {
        $this->areaService = $areaService;
        $this->couponsService = $couponsService;
        $this->brandService = $brandService;
        $this->categoryService = $categoryService;
        $this->storeService = $storeService;
        $this->categoryGoodsService = $categoryGoodsService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->config();
        $this->sessionRepository = $sessionRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->userCommonService = $userCommonService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['error' => 1, 'message' => 'referer error']);
        }

        load_helper(['clips', 'transaction']);

        $this->dscRepository->helpersLang('user');

        assign_template();
        /*------------------------------------------------------ */
        //-- INPUT
        /*------------------------------------------------------ */

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

        //by wu
        $act = addslashes(request()->input('act', ''));
        $tpl = addslashes(request()->input('tpl', 1));

        $this->smarty->assign('tpl', $tpl); //by wu

        $user_id = session('user_id', 0);

        if ($act == 'get_content') {

            $result = $this->commonService->getContent($user_id, $warehouse_id, $area_id, $area_city);

            return response()->json($result);
        } /**
         * 登录弹框
         */
        elseif ($act == 'get_login_dialog') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $back_act = addslashes(trim(request()->input('back_act', '')));
            $dsc_token = get_dsc_token();
            $this->smarty->assign('dsc_token', $dsc_token);

            /* 验证码相关设置 */
            $captcha = intval($this->config['captcha']);
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && session('login_fail') > 2)) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            /* 获取安装的社会化登录列表 */
            $WebsiteList = $this->userCommonService->getWebsiteList();
            if ($WebsiteList) {
                foreach ($WebsiteList as $k => $v) {
                    // 未安装不显示
                    if ($v['install'] == 0) {
                        unset($WebsiteList[$k]);
                    }
                    if ($v['type'] == 'wechat') {
                        unset($WebsiteList[$k]); // 不显示微信授权登录（微信端）
                    }
                }
            }

            $this->smarty->assign('website_list', $WebsiteList);
            // 来源地址
            if (empty($back_act)) {
                if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
                    $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], route('user')) ? route('user') : $GLOBALS['_SERVER']['HTTP_REFERER'];
                } else {
                    $back_act = route('user');
                }
            }

            $is_jsonp = $this->dscRepository->isJsonp($back_act);
            $this->smarty->assign('is_jsonp', $is_jsonp);

            $this->smarty->assign('back_act', $back_act);
            $this->smarty->assign('user_lang', $GLOBALS['_LANG']);
            $result['content'] = $this->smarty->fetch('library/login_dialog_body.lbi');
            return response()->json($result);
        } /*门店弹窗  by kong*/
        elseif ($act == 'get_store_list') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = (int)request()->input('goods_id', 0);
            //商品属性
            $spec_arr = addslashes(request()->input('spec_arr', ''));

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('provinces', get_regions(1, 1));
            $this->smarty->assign('goods_id', $goods_id);

            /*获取全部门店信息*/
            $where = [
                'is_confirm' => 1,
                'goods_id' => $goods_id
            ];
            $store_list = $this->storeService->getStoreList($where);

            $is_spec = $spec_arr ? explode(',', $spec_arr) : [];
            if (!empty($store_list)) {
                foreach ($store_list as $k => $v) {
                    if (is_spec($is_spec) == true) {
                        $products = $this->goodsWarehouseService->getWarehouseAttrNumber($v['goods_id'], $spec_arr, $this->warehouse_id, $this->area_id, $this->area_city, '', $v['id']); //获取属性库存
                        $v['goods_number'] = $products ? $products['product_number'] : 0;
                        if ($v['goods_number'] == 0) {
                            unset($store_list[$k]);
                        }
                    }
                }
            }

            $this->smarty->assign('store_list', $store_list);
            $result['content'] = $this->smarty->fetch('library/store_list_body.lbi');
            return response()->json($result);
        } /*预约到店弹窗  by kong*/
        elseif ($act == 'storePick' || $act == 'storeSelect' || $act == 'replaceStore') {
            //load_helper('area');  //ecmoban模板堂 --zhuo
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $goods_id = (int)request()->input('goods_id', 0);
            $city_id = (int)request()->input('city_id', 0);
            //商品属性
            $spec_arr = addslashes(request()->input('spec_arr', ''));
            $ru_id = (int)request()->input('ru_id', 0);
            $store_id = (int)request()->input('store_id', 0);

            $area_position_list = get_goods_user_area_position($ru_id, $warehouse_id, $area_id, $area_city, $city_id, $spec_arr, $goods_id, 0, 0, 0, $store_id);
            $this->smarty->assign('area_position_list', $area_position_list);

            if ($act == 'storePick') {
                $user_id = session('user_id') ?? 0;
                if ($user_id) {
                    $mobile_phone = $this->userCommonService->getUserField(['user_id' => $user_id], 'mobile_phone');
                }
                $take_time = local_date("Y-m-d H:i:s", strtotime("+1 day"));
                $now_time = local_date("Y-m-d H:i:s", gmtime());
                $this->smarty->assign("mobile_phone", $mobile_phone ?? '');
                $this->smarty->assign("now_time", $now_time);
                $this->smarty->assign("take_time", $take_time);
                $result['content'] = $this->smarty->fetch('library/goods_store_pick.lbi');
            } elseif ($act == 'replaceStore') {
                $this->smarty->assign("temp", $act);
                $result['content'] = $this->smarty->fetch('library/store_select_shop.lbi');
            } else {
                /*获取默认地址信息*/
                $this->smarty->assign('province_name', get_shop_address($this->province_id));
                $this->smarty->assign('city_name', get_shop_address($this->city_id));
                $this->smarty->assign('district_name', get_shop_address($this->district_id));
                $provinces = get_regions(1, 1);
                foreach ($provinces as $k => $v) {
                    if (count(get_goods_user_area_position($ru_id, $warehouse_id, $area_id, $area_city, $this->city_id, $spec_arr, $goods_id, $v['region_id'], 0, 1)) > 0) {
                        $provinces[$k]['store_count'] = 1;
                    } else {
                        $provinces[$k]['store_count'] = 0;
                    }
                }
                $this->smarty->assign('provinces', $provinces);
                $result['content'] = $this->smarty->fetch('library/goods_lately_store_pick.lbi');
            }
            return response()->json($result);
        } /*切换地址 获取地址信息  by kong*/
        elseif ($act == 'getstoreRegion' || $act == 'get_parent_regions') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $warehouse_id = (int)request()->input('value', 0);
            $level = (int)request()->input('level', 1);

            if ($act == 'getstoreRegion') {
                $level = $level + 1;
            }

            $ru_id = (int)request()->input('ru_id', 0);
            //商品属性
            $spec_arr = addslashes(request()->input('spec_arr', ''));
            $goods_id = (int)request()->input('goods_id', 0);

            if ($act == 'get_parent_regions') {
                $region = get_parent_regions($warehouse_id);
            } else {
                $region = get_regions($level, $warehouse_id);
            }
            $html = '';
            foreach ($region as $k => $v) {
                if ($v['region_id'] > 0) {
                    switch ($level) {
                        case 1:
                            $type = count(get_goods_user_area_position($ru_id, $warehouse_id, $area_id, $area_city, $this->city_id, $spec_arr, $goods_id, $v['region_id'], 0, 1));
                            break;
                        case 2:
                            $type = count(get_goods_user_area_position($ru_id, $warehouse_id, $area_id, $area_city, $v['region_id'], $spec_arr, $goods_id, 0, 0, 1));
                            break;
                        case 3:
                            $type = count(get_goods_user_area_position($ru_id, $warehouse_id, $area_id, $area_city, $this->city_id, $spec_arr, $goods_id, 0, $v['region_id'], 1));
                            break;
                    }
                    $store_count = '';
                    if ($type > 0) {
                        $store_count = "<i></i>";
                    }
                    $html .= '<a href="javascript:void(0);" data-level="' . $level . '" data-id="' . $v['region_id'] . '" data-name="' . $v['region_name'] . '" class="city-item">' . $v['region_name'] . $store_count . '</a>';
                }
            }
            $result['html'] = $html;
            return response()->json($result);
        } /*编辑门店订单到店时间  电话*/
        elseif ($act == 'checked_store_info') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $cart_value = addslashes(request()->input('cart_value', ''));
            $end_time = addslashes(request()->input('end_time', ''));
            $store_mobile = addslashes(request()->input('store_mobile', ''));

            if ($store_mobile == '') {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['store_take_mobile'];
            } else {
                if ($cart_value) {
                    $cart_value = !is_array($cart_value) ? explode(",", $cart_value) : $cart_value;

                    $other = [
                        'take_time' => $end_time,
                        'store_mobile' => $store_mobile
                    ];
                    Cart::whereIn('rec_id', $cart_value)->update($other);
                }
            }

            return response()->json($result);
        } /* 储值卡充值弹窗 */
        elseif ($act == 'to_pay_card') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $vid = (int)request()->input('vid', 0);

            $this->smarty->assign('vid', $vid);
            $result['content'] = $this->smarty->fetch('library/to_pay_body.lbi');
            return response()->json($result);
        } /* 储值卡解绑弹窗 */
        elseif ($act == 'remove_bind') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $vid = (int)request()->input('vid', 0);
            /* 短信验证码参数 */
            $this->smarty->assign('user_info', get_user_info());

            if (intval($this->config['sms_signin']) > 0) {
                $sms_security_code = rand(1000, 9999);

                session([
                    'sms_security_code' => $sms_security_code
                ]);

                $this->smarty->assign('sms_security_code', $sms_security_code);
            }

            $this->smarty->assign('vid', $vid);
            $result['content'] = $this->smarty->fetch('library/remove_bind_body.lbi');
            return response()->json($result);
        } /**
         * 获得顶级分类页面楼层
         */
        elseif ($act == 'get_cat_top_list' && $tpl == 1) {
            $warehouse_id = (int)request()->input('region_id', 0);
            $area_id = (int)request()->input('area_id', 0);
            $area_city = (int)request()->input('area_city', 0);
            $cat_id = (int)request()->input('cat_id', 0);
            $prent_id = (int)request()->input('prent_id', 0);
            $rome_key = (int)request()->input('rome_key', 0) + 1;

            $result = ['error' => 0, 'content' => ''];

            /* End */

            if ($cat_id > 0) {
                $categories_child = $this->categoryService->getCatList($prent_id);
                $one_cate_child = $categories_child[$cat_id];

                //获取分类的品牌和商品
                if ($one_cate_child) {
                    $one_cate_child['brands'] = $this->brandService->getBrands($cat_id, 'brand', 10);

                    foreach ($one_cate_child['cat_list'] as $k => $v) {
                        $child_children = $this->categoryService->getCatListChildren($v['cat_id']);
                        $childcate_goods_list = $this->categoryGoodsService->getCategoryRecommendGoods($child_children, 'best', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 10);

                        if ($childcate_goods_list) {
                            $one_cate_child['cat_list'][$k]['goods_list'] = $childcate_goods_list;
                        }
                    }

                    $cat_top_floor_ad = '';
                    for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                        $cat_top_floor_ad .= "'cat_top_floor_ad" . $i . ","; //首页楼层左侧广告图
                    }

                    //楼层右侧广告
                    $cat_top_floor_ad_right = '';
                    for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                        $cat_top_floor_ad_right .= "'cat_top_floor_ad_right" . $i . ","; //首页楼层左侧广告图
                    }
                    $floor_ad_arr_right = ["ad_arr" => $cat_top_floor_ad_right, "id" => $cat_id];
                    $cat_top_floor_ad_right = insert_get_adv_child($floor_ad_arr_right);
                    $this->smarty->assign('cat_top_floor_ad_right', $cat_top_floor_ad_right);

                    //楼层广告
                    $floor_ad_arr = ["ad_arr" => $cat_top_floor_ad, "id" => $cat_id];
                    $cat_top_floor_ad = insert_get_adv_child($floor_ad_arr);

                    //楼层底部广告
                    $this->smarty->assign('rome_number', $rome_key);
                    $this->smarty->assign('cat_top_floor_ad', $cat_top_floor_ad);
                    $this->smarty->assign('one_cate_child', $one_cate_child);
                    $result['content'] = html_entity_decode($this->smarty->fetch('library/load_category_top.lbi'));
                } else {
                    $result['error'] = 1;
                }
            }

            if (count($categories_child) == $rome_key) {
                $result['maxindex'] = true;
            }

            $result['cat_id'] = $cat_id;
            $result['rome_key'] = $rome_key;

            return response()->json($result);
        } /**
         * 加载首页楼层
         */
        elseif ($act == 'get_index_goods_cat') {
            $rome_key = (int)request()->input('rome_key', 0);
            $result = ['error' => 0, 'content' => '', 'maxindex' => 0];

            //将数据写入缓存文件 by wang
            if (!read_static_cache('index_goods_cat_cache')) {
                $template = Template::where('filename', 'index')
                    ->where('type', 1)
                    ->where('theme', $this->config['template'])
                    ->where('remarks', '')
                    ->orderBy('sort_order', 'desc');

                $template = $template->get();

                $template = $template ? $template->toArray() : [];

                write_static_cache('index_goods_cat_cache', $template);
            } else {
                $template = read_static_cache('index_goods_cat_cache');
            }

            if ($template && $rome_key <= count($template) - 1) {
                $row = $template[$rome_key];

                //获取楼层设置内容
                $brand_ids = insert_get_floor_content($row);
                $brands_theme2 = get_floor_brand($brand_ids);
                $brands_theme2 = get_brands_theme2($brands_theme2);

                $this->smarty->assign('brands_theme2', $brands_theme2);
                $region_store_enabled = '';
                if ($this->config['region_store_enabled'] == 1) {
                    $areaCookie = $this->areaService->areaCookie();
                    $region_store_enabled = "_" . $areaCookie['city'];
                }

                $goods_cat = read_static_cache('index_goods_cat' . $rome_key . "_" . session('user_rank') . $region_store_enabled);
                if ($goods_cat === false) {
                    $goods_cat = assign_cat_goods($row['id'], $row['number'], 'web', '', 'cat', $warehouse_id, $area_id, $area_city, $rome_key);
                    write_static_cache('index_goods_cat' . $rome_key . "_" . session('user_rank') . $region_store_enabled, $goods_cat);
                }

                if ($goods_cat) {
                    //广告
                    $get_adv = insert_get_adv(['logo_name' => $goods_cat['floor_banner']]);
                    $this->smarty->assign('get_adv', $get_adv);
                    $cat_goods_banner = '';
                    $cat_goods_hot = '';
                    $cat_goods_ad_left = '';
                    $cat_goods_ad_right = '';
                    /*             * 小图 start* */
                    for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                        $cat_goods_banner .= "'cat_goods_banner" . $i . ","; //首页楼层轮播图
                        $cat_goods_hot .= "'cat_goods_hot" . $i . ","; //首页楼层轮播图
                        $cat_goods_ad_left .= "'cat_goods_ad_left" . $i . ","; //首页楼层左侧广告图
                        $cat_goods_ad_right .= "'cat_goods_ad_right" . $i . ","; //首页楼层右侧广告图
                    }
                    $cat_goods_hot = insert_get_adv_child(['ad_arr' => $cat_goods_hot, 'id' => $goods_cat['id'], "warehouse_id" => $warehouse_id, "area_id" => $area_id]);

                    $goods_cat['floor_style_tpl'] = isset($row['floor_tpl']) ? intval($row['floor_tpl']) : 0;
                    $cat_goods_banner = insert_get_adv_child(['ad_arr' => $cat_goods_banner, 'id' => $goods_cat['id'], "warehouse_id" => $warehouse_id, "area_id" => $area_id, "floor_style_tpl" => $goods_cat['floor_style_tpl']]);
                    $cat_goods_ad_left = insert_get_adv_child(['ad_arr' => $cat_goods_ad_left, 'id' => $goods_cat['id'], "warehouse_id" => $warehouse_id, "area_id" => $area_id, "floor_style_tpl" => $goods_cat['floor_style_tpl']]);
                    $cat_goods_ad_right = insert_get_adv_child(['ad_arr' => $cat_goods_ad_right, 'id' => $goods_cat['id'], "warehouse_id" => $warehouse_id, "area_id" => $area_id, "floor_style_tpl" => $goods_cat['floor_style_tpl']]);

                    $this->smarty->assign('cat_goods_banner', $cat_goods_banner);
                    $this->smarty->assign('cat_goods_hot', $cat_goods_hot);
                    $this->smarty->assign('cat_goods_ad_left', $cat_goods_ad_left);
                    $this->smarty->assign('cat_goods_ad_right', $cat_goods_ad_right);
                    $this->smarty->assign('goods_cat', $goods_cat);
                    $result['content'] = html_entity_decode($this->smarty->fetch('library/load_cat_goods.lbi'));
                } else {
                    $result['error'] = 1;
                }
                if ($rome_key == count($template) - 1) {
                    $result['maxindex'] = 1;
                }
            } else {
                $result['error'] = 1;
            }

            return response()->json($result);
        } /**
         * 加载分类品牌
         */
        elseif ($act == 'getCategotyParentTree') {
            $cat_id = (int)request()->input('cat_id', 0);
            $defa = (int)request()->input('defa', 0);

            $result = ['error' => 0, 'content' => ''];

            $file = "parent_style_brands" . $cat_id;
            $brands = read_static_cache($file);

            //将数据写入缓存文件 by wang
            if ($brands === false) {
                $brands = $this->brandService->getBrands($cat_id);
                write_static_cache($file, $brands);
            }

            $this->smarty->assign('brands', $brands);
            $this->smarty->assign('defa', $defa);

            $result['cat_id'] = $cat_id;
            $result['brands_content'] = $this->smarty->fetch('library/category_parent_brands.lbi');

            return response()->json($result);
        } /**
         * 获得顶级分类页面楼层(模板2)
         */
        elseif ($act == 'get_cat_top_list' && $tpl == 2) {
            $warehouse_id = (int)request()->input('region_id', 0);
            $area_id = (int)request()->input('area_id', 0);
            $area_city = (int)request()->input('area_city', 0);
            $cat_id = (int)request()->input('cat_id', 0);
            $prent_id = (int)request()->input('prent_id', 0);
            $rome_key = (int)request()->input('rome_key', 0) + 1;

            $result = ['error' => 0, 'content' => ''];

            if ($cat_id > 0) {
                $categories_child = $this->categoryService->getCatList($prent_id);
                $one_cate_child = $categories_child[$cat_id];

                //获取分类的品牌和商品
                if ($one_cate_child) {
                    $one_cate_child['brands'] = $this->brandService->getBrands($cat_id, 'brand', 10);

                    foreach ($one_cate_child['cat_list'] as $k => $v) {
                        $child_children = $this->categoryService->getCatListChildren($v['cat_id']);
                        $childcate_goods_list = $this->categoryGoodsService->getCategoryRecommendGoods($child_children, 'best', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 10);

                        if ($childcate_goods_list) {
                            $one_cate_child['cat_list'][$k]['goods_list'] = $childcate_goods_list;
                        }
                    }

                    //热销商品
                    $children = $this->categoryService->getCatListChildren($cat_id);
                    $one_cate_child['goods_hot'] = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'hot', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 1);

                    $top_style_elec_left = '';
                    for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                        $top_style_elec_left .= "'top_style_elec_left" . $i . ","; //首页楼层左侧广告图
                    }

                    //楼层广告(left)
                    $floor_ad_arr = ["ad_arr" => $top_style_elec_left, "id" => $cat_id];
                    $top_style_elec_left = insert_get_adv_child($floor_ad_arr);

                    //楼层广告(row)
                    $top_style_elec_row = "'top_style_elec_row,";
                    $floor_ad_arr = ["ad_arr" => $top_style_elec_row, "id" => $cat_id];
                    $top_style_elec_row = insert_get_adv_child($floor_ad_arr);

                    $class_num = ['on', '', 'last'];

                    //楼层底部广告
                    $this->smarty->assign('class_num', $class_num);
                    $this->smarty->assign('rome_number', $rome_key);
                    $this->smarty->assign('top_style_elec_left', $top_style_elec_left);
                    $this->smarty->assign('top_style_elec_row', $top_style_elec_row);
                    $this->smarty->assign('one_cate_child', $one_cate_child);
                    $result['content'] = html_entity_decode($this->smarty->fetch('library/load_category_top.lbi'));
                } else {
                    $result['error'] = 1;
                }
            }

            if (count($categories_child) == $rome_key) {
                $result['maxindex'] = true;
            }

            $result['cat_id'] = $cat_id;
            $result['rome_key'] = $rome_key;

            return response()->json($result);
        } /**
         * 获得顶级分类页面楼层(模板3)
         */
        elseif ($act == 'get_cat_top_list' && $tpl == 3) {
            $warehouse_id = (int)request()->input('region_id', 0);
            $area_id = (int)request()->input('area_id', 0);
            $area_city = (int)request()->input('area_city', 0);
            $cat_id = (int)request()->input('cat_id', 0);
            $prent_id = (int)request()->input('prent_id', 0);
            $rome_key = (int)request()->input('rome_key', 0) + 1;

            $result = ['error' => 0, 'content' => ''];

            if ($cat_id > 0) {
                $categories_child = $this->categoryService->getCatList($prent_id);
                $one_cate_child = $categories_child[$cat_id];

                //获取分类的品牌和商品
                if ($one_cate_child) {
                    $one_cate_child['brands'] = $this->brandService->getBrands($cat_id, 'brand', 10);

                    foreach ($one_cate_child['cat_list'] as $k => $v) {
                        $child_children = $this->categoryService->getCatListChildren($v['cat_id']);
                        $childcate_goods_list = $this->categoryGoodsService->getCategoryRecommendGoods($child_children, 'best', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 10);

                        if ($childcate_goods_list) {
                            $one_cate_child['cat_list'][$k]['goods_list'] = $childcate_goods_list;
                        }
                    }

                    //热销商品
                    $children = $this->categoryService->getCatListChildren($cat_id);
                    $one_cate_child['goods_hot'] = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'hot', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 4);

                    $top_style_food_left = '';
                    for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                        $top_style_food_left .= "'top_style_food_left" . $i . ","; //首页楼层左侧广告图
                    }

                    //楼层广告(left)
                    $floor_ad_arr = ["ad_arr" => $top_style_food_left, "id" => $cat_id];
                    $top_style_food_left = insert_get_adv_child($floor_ad_arr);

                    //楼层广告(row)
                    $top_style_food_row = "'top_style_food_row,";
                    $floor_ad_arr = ["ad_arr" => $top_style_food_row, "id" => $cat_id];
                    $top_style_food_row = insert_get_adv_child($floor_ad_arr);

                    $class_num = ['on', '', 'last'];

                    //楼层底部广告
                    $this->smarty->assign('class_num', $class_num);
                    $this->smarty->assign('rome_number', $rome_key);
                    $this->smarty->assign('top_style_food_left', $top_style_food_left);
                    $this->smarty->assign('top_style_food_row', $top_style_food_row);
                    $this->smarty->assign('one_cate_child', $one_cate_child);
                    $result['content'] = html_entity_decode($this->smarty->fetch('library/load_category_top.lbi'));
                } else {
                    $result['error'] = 1;
                }

                if (count($one_cate_child) == $rome_key) {
                    $result['maxindex'] = true;
                }
            }

            $result['cat_id'] = $cat_id;
            $result['rome_key'] = $rome_key;

            return response()->json($result);
        }

        //换一组 by wu
        //type 1:随便看看 2:品牌 3:分类商品
        elseif ($act == 'changeShow') {
            $warehouse_id = (int)request()->input('region_id', 0);
            $area_id = (int)request()->input('area_id', 0);
            $area_city = (int)request()->input('area_city', 0);
            $cat_id = (int)request()->input('cat_id', 0);
            $type = (int)request()->input('type', 0);
            $tpl = (int)request()->input('tpl', 0);

            $this->smarty->assign('type', $type); //输出类型
            $this->smarty->assign('tpl', $tpl); //输出模板

            $result = ['error' => 0, 'content' => ''];

            if ($type == 1) {
                $child_children = get_children($cat_id);
                $havealook = $this->categoryGoodsService->getCategoryRecommendGoods($child_children, 'rand', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 6);
                $this->smarty->assign('havealook', $havealook);
                $result['page'] = $this->smarty->fetch('library/have_a_look.lbi');
            } elseif ($type == 3) {
                if ($cat_id > 0) {
                    if ($tpl == 2) {
                        $child_children = get_children($cat_id);
                        $goods_list = $this->categoryGoodsService->getCategoryRecommendGoods($child_children, 'rand', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 8);
                        $this->smarty->assign('goods_list', $goods_list);
                        $result['page'] = html_entity_decode($this->smarty->fetch('library/cat_goods_change.lbi'));
                    } elseif ($tpl == 3) {
                        $child_children = get_children($cat_id);
                        $goods_list = $this->categoryGoodsService->getCategoryRecommendGoods($child_children, 'rand', 0, $warehouse_id, $area_id, $area_city, '', 0, 0, 6);
                        $this->smarty->assign('goods_list', $goods_list);
                        $result['page'] = html_entity_decode($this->smarty->fetch('library/cat_goods_change.lbi'));
                    }
                } else {
                    $result['error'] = 1;
                }
            }

            return response()->json($result);
        } /**
         * 首页楼层鼠标移动分类触发事件
         */
        elseif ($act == 'floor_cat_content') {
            $result = ['error' => 0, 'content' => ''];

            /* 过滤 XSS 攻击和SQL注入 */
            get_request_filter();

            $goods_ids = addslashes(request()->input('goods_ids', ''));
            $cat_id = (int)request()->input('cat_id', 0);
            $floor_num = (int)request()->input('floor_num', 0);

            $warehouse_id = (int)request()->input('warehouse_id', 0);
            $area_id = (int)request()->input('area_id', 0);
            $area_city = (int)request()->input('area_city', 0);

            $seller_id = (int)request()->input('seller_id', 0);
            //模板标识
            $floorcat = (int)request()->input('floorcat', 0);

            $result['cat_id'] = $cat_id;

            $region_store_enabled = '';
            if ($this->config['region_store_enabled'] == 1) {
                $areaCookie = $this->areaService->areaCookie();
                $region_store_enabled = "_" . $areaCookie['city'];
            }

            $file = "floor_cat_content_" . $cat_id . "_" . session('user_rank') . "_" . $floor_num . "_" . $seller_id . $region_store_enabled;
            if (empty($goods_ids)) {
                $goods_list = read_static_cache($file);
            } else {
                $goods_list = false;
            }

            //将数据写入缓存文件 by wang
            if ($goods_list === false) {
                $children = $this->categoryService->getCatListChildren($cat_id);
                $goods_list = $this->goodsCommonService->getFloorAjaxGoods($children, $floor_num, $warehouse_id, $area_id, $area_city, $goods_ids, $seller_id);
                if (empty($goods_ids)) {
                    write_static_cache($file, $goods_list);
                }
            }

            $this->smarty->assign('goods_list', $goods_list);

            $temp = "floor_temp";
            if ($floorcat == 1) {
                $temp = "floor_temp_expand";
            }
            $this->smarty->assign("temp", $temp);

            if ($floorcat == 2) {
                $result['content'] = $goods_list;
            } else {
                $defaultnumber = $floor_num - count($goods_list);
                $defaultgoods = [];
                if ($defaultnumber > 0) {
                    $defaultgoods = range(1, $defaultnumber);
                }
                $this->smarty->assign("defaultgoods", $defaultgoods);
                $result['content'] = $this->smarty->fetch('library/floor_cat_content.lbi');
            }
            return response()->json($result);
        } /**
         * 首页楼层鼠标移动分类触发事件
         */
        elseif ($act == 'cart_info') {

            $result = $this->commonService->domainCartInfo();

            return response()->json($result);
        } /**
         * 店铺街关注店铺  取消店铺
         */
        elseif ($act == 'ajax_store_collect') {

            $result = $this->commonService->ajaxStoreCollect($user_id);

            return response()->json($result);
        } /**
         * 领取优惠券-----Ajax
         */
        elseif ($act == 'ajax_coupons_receive') {

            $cou_id = (int)request()->input('cou_id', 0);
            $result = $this->couponsService->Couponsreceive($cou_id, $user_id);

            return response()->json($result);
        } /**
         * 首页品牌换一批Ajax
         */
        elseif ($act == 'ajax_change_brands') {
            $result = ['error' => 0, 'content' => ''];

            $temp = addslashes(trim(request()->input('temp', '')));
            $limit = 17;
            if ($temp == 'backup_festival_1') {
                $limit = 29;
            }

            $recommend_brands = Brand::where('is_show', 1);

            $recommend_brands = $recommend_brands->withCount([
                'getCollectBrand as collect_count' => function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                }
            ]);

            $recommend_brands = $recommend_brands->orderByRaw('RAND()')
                ->take($limit)
                ->get();

            $recommend_brands = $recommend_brands ? $recommend_brands->toArray() : [];

            if ($recommend_brands) {
                foreach ($recommend_brands as $key => $val) {
                    $recommend_brands[$key]['brand_logo'] = empty($val['brand_logo']) ? str_replace(['../'], '', $this->config['no_brand']) : $val['brand_logo'];

                    if ($val['site_url'] && strlen($val['site_url']) > 8) {
                        $recommend_brands[$key]['url'] = $val['site_url'];
                    } else {
                        $recommend_brands[$key]['url'] = $this->dscRepository->buildUri('brandn', ['bid' => $val['brand_id']], $val['brand_name']);
                    }


                    $recommend_brands[$key]['is_collect'] = $val['collect_count'];

                    $recommend_brands[$key]['brand_logo'] = get_image_path(DATA_DIR . '/brandlogo/' . $recommend_brands[$key]['brand_logo']);
                }
            }

            if (count($recommend_brands) > 0) {
                $need_cache = $this->smarty->caching;
                $need_compile = $this->smarty->force_compile;

                $this->smarty->caching = false;
                $this->smarty->force_compile = true;

                $this->smarty->assign('recommend_brands', $recommend_brands);
                $GLOBALS['smarty']->assign('temp', $temp);
                $result['content'] = $this->smarty->fetch('library/index_brand_street.lbi');

                $this->smarty->caching = $need_cache;
                $this->smarty->force_compile = $need_compile;
            }

            return response()->json($result);
        } /**
         * 查看物流
         */
        elseif ($act == 'view_logistics_info') {
            $result = ['error' => 0, 'content' => ''];

            $order_id = (int)request()->input('order_id', 0);
            $invoice_no = addslashes(trim(request()->input('invoice_no', '')));

            //按照发货单的发货方式查询物流信息
            $res = DeliveryOrder::where('order_id', $order_id)->where('invoice_no', $invoice_no)->with(['getShipping'])->first();
            $res = $res ? $res->toArray() : [];

            if ($res) {
                $shipping = $res['get_shipping'] ?? [];
                $res = array_merge($res, $shipping);
                $res['shipping_code'] = $res['shipping_code'] ?? '';
                $shippingObject = app(CommonRepository::class)->shippingInstance($res['shipping_code']);
                $result['expressid'] = is_null($shippingObject) ? '' : $shippingObject->get_code_name();
            }

            $result['expressid'] = $result['expressid'] ?? '';
            $result['expressno'] = $invoice_no;

            return response()->json($result);
        } /**
         * 加载地区
         */
        elseif ($act == 'insert_header_region') {
            $result = ['error' => 0, 'content' => ''];

            $this->smarty->assign('is_insert', 1);

            $result['content'] = insert_header_region();

            return response()->json($result);
        } elseif ($act == 'select_regionChild') {
            $result = ['error' => 0, 'message' => '', 'content' => '', 'ra_id' => '', 'region_id' => ''];

            $region = strip_tags(urldecode(request()->input('region', '')));
            $region = json_str_iconv($region);
            $region = dsc_decode($region);

            $city_list = Region::select('region_id', 'region_name')->where('parent_id', $region->region_id)->get();
            $city_list = $city_list ? $city_list->toArray() : [];

            $time = 60 * 24 * 30;
            $result['city_list'] = 0;
            if ($region->type == 0) {
                if (empty($city_list)) {
                    $province = $region->region_id;
                }
            } elseif ($region->type == 1) {
                cookie()->queue('type_province', $region->region_id, $time);
            } elseif ($region->type == 2) {
                cookie()->queue('type_city', $region->region_id, $time);
            }

            if (empty($city_list)) {
                $result['city_list'] = 1;

                $province = $this->sessionRepository->getCookie('type_province');
                $city = $this->sessionRepository->getCookie('type_city');
            }

            /* 删除缓存 */
            $this->areaService->getCacheNameForget('area_cookie');
            $this->areaService->getCacheNameForget('area_info');
            $this->areaService->getCacheNameForget('warehouse_id');

            $area_cache_name = $this->areaService->getCacheName('area_cookie');

            $area_cookie_cache = [
                'province' => $province,
                'city_id' => $city,
                'district' => 0,
                'street' => 0,
                'street_area' => ''
            ];

            cache()->forever($area_cache_name, $area_cookie_cache);

            /* 地区缓存 ra_id */
            $raid_cache_name = $this->areaService->getCacheName('raid_cookie');
            cache()->forever($raid_cache_name, $region->ra_id);

            $GLOBALS['smarty']->assign('city_list', $city_list);
            $GLOBALS['smarty']->assign('type', $region->type);

            $areaCookie = $this->areaService->areaCookie();
            $GLOBALS['smarty']->assign('city_top', $areaCookie['city']);
            $GLOBALS['smarty']->assign('district_top', $areaCookie['district']);

            $result['ra_id'] = $region->ra_id;
            $result['type'] = $region->type;
            $result['region_id'] = $region->region_id;
            $result['content'] = $GLOBALS['smarty']->fetch("library/merchants_city_list.lbi");

            clear_all_files();

            return response()->json($result);
        } elseif ($act == 'select_district_list') {
            $result = ['error' => 0, 'message' => '', 'content' => '', 'ra_id' => '', 'region_id' => ''];

            $region = strip_tags(urldecode(request()->input('region', '')));
            $region = json_str_iconv($region);
            $region = dsc_decode($region);

            $province = Region::where('region_id', $region->region_id)->value('parent_id');

            $district_list = Region::select('region_id', 'region_name')
                ->where('parent_id', $region->region_id)
                ->orderBy('region_id')
                ->take(1)
                ->get();
            $district_list = $district_list ? $district_list->toArray() : [];

            if ($region->type == 0) {
                $city = $region->region_id;

                $this->district_id = 0;
                if ($district_list) {
                    $this->district_id = $district_list[0]['region_id'];
                }

                $district = $this->district_id;

                $street_list = 0;
                $street_id = 0;
                if ($this->district_id) {
                    $street_info = Region::select('region_id')->where('parent_id', $this->district_id)->get();
                    $street_info = $street_info ? $street_info->toArray() : [];
                    $street_info = $street_info ? collect($street_info)->pluck('region_id')->all() : [];

                    if ($street_info) {
                        $street_id = $street_info[0];
                        $street_list = implode(",", $street_info);
                    }
                }

                /* 删除缓存 */
                $this->areaService->getCacheNameForget('area_cookie');
                $this->areaService->getCacheNameForget('area_info');
                $this->areaService->getCacheNameForget('warehouse_id');

                $area_cache_name = $this->areaService->getCacheName('area_cookie');

                $area_cookie_cache = [
                    'province' => $province,
                    'city_id' => $city,
                    'district' => $district,
                    'street' => $street_id,
                    'street_area' => $street_list
                ];

                cache()->forever($area_cache_name, $area_cookie_cache);

                $region_top = get_warehouse_goods_region($province);
                if ($region_top) {
                    cookie()->queue('area_region', $region_top['region_id'], 60 * 24 * 30);
                }

                //清空
                $time = 60 * 24 * 30;
                cookie()->queue('type_province', 0, $time);
                cookie()->queue('type_city', 0, $time);
                cookie()->queue('type_district', 0, $time);
            } else {
                $time = 60 * 24 * 30;
                cookie()->queue('type_district', $region->region_id, $time);
            }

            clear_all_files();

            return response()->json($result);
        }
    }
}
