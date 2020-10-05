<?php

namespace App\Http\Controllers;

use App\Models\CartCombo;
use App\Models\Goods;
use App\Models\OfflineStore;
use App\Models\OrderGoods;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\StoreProducts;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Erp\JigonManageService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;
use App\Services\User\UserCommonService;

/**
 * 商品详情
 */
class GoodsController extends InitController
{
    protected $areaService;
    protected $goodsService;
    protected $dscRepository;
    protected $categoryService;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $userCommonService;
    protected $sessionRepository;
    protected $timeRepository;
    protected $config;
    protected $articleCommonService;
    protected $orderGoodsService;

    public function __construct(
        AreaService $areaService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        CategoryService $categoryService,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        UserCommonService $userCommonService,
        SessionRepository $sessionRepository,
        TimeRepository $timeRepository,
        ArticleCommonService $articleCommonService,
        OrderGoodsService $orderGoodsService
    )
    {
        $this->areaService = $areaService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->userCommonService = $userCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->timeRepository = $timeRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->orderGoodsService = $orderGoodsService;
    }

    public function index()
    {

        /**
         * Start
         *
         * @param int $warehouse_id 仓库ID
         * @param int $area_id 省份ID
         * @param int $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();

        $affiliate = $this->config['affiliate'] ? unserialize($this->config['affiliate']) : [];
        $this->smarty->assign('affiliate', $affiliate);
        $factor = intval($this->config['comment_factor']);
        $this->smarty->assign('factor', $factor);

        $now = $this->timeRepository->getGmTime();

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $web = app(CrossBorderService::class)->webExists();

            if (!empty($web)) {
                $web->smartyAssign();
            }
        }

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        $act = addslashes(request()->input('act', ''));
        $goods_id = (int)request()->input('id', 0);
        $pid = (int)request()->input('pid', 0);

        $user_id = session('user_id', 0);

        /* 跳转H5 start */
        $Loaction = dsc_url('/#/goods/' . $goods_id);
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        $this->smarty->assign('category', $goods_id);

        //参数不存在则跳转回首页
        if (empty($goods_id)) {
            return redirect("/");
        }

        /* 查看是否秒杀商品 */
        $sec_goods_id = $this->goodsService->get_is_seckill($goods_id);
        if ($sec_goods_id) {
            $seckill_url = $this->dscRepository->buildUri('seckill', array('act' => "view", 'secid' => $sec_goods_id));
            return dsc_header("Location: $seckill_url\n");
        }

        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'is_delete' => 0
        ];
        $goods = $this->goodsService->getGoodsInfo($where);

        /*------------------------------------------------------ */
        //-- 改变属性、数量时重新计算商品价格
        /*------------------------------------------------------ */

        if (!empty($act) && $act == 'price') {
            if ($this->checkReferer() === false) {
                return response()->json(['err_no' => 1, 'err_msg' => 'referer error']);
            }

            $res = ['err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1];

            $get_attr_id = request()->input('attr', '');
            $attr_id = $get_attr_id ? explode(',', $get_attr_id) : [];

            $number = (int)request()->input('number', 1);
            $warehouse_id = (int)request()->input('warehouse_id', 0);
            //仓库管理的地区ID
            $area_id = (int)request()->input('area_id', 0);
            //加载类型
            $type = (int)request()->input('type', 0);

            $goods_attr = request()->input('goods_attr', '');
            $goods_attr = $goods_attr ? explode(',', $goods_attr) : [];

            $attr_ajax = $this->goodsService->getGoodsAttrAjax($goods_id, $goods_attr, $attr_id);

            if ($goods_id == 0) {
                $res['err_msg'] = $GLOBALS['_LANG']['err_change_attr'];
                $res['err_no'] = 1;
            } else {
                if ($number == 0) {
                    $res['qty'] = $number = 1;
                } else {
                    $res['qty'] = $number;
                }

                //ecmoban模板堂 --zhuo start
                $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_id, $get_attr_id, $warehouse_id, $area_id, $area_city);
                $attr_number = isset($products['product_number']) ? $products['product_number'] : 0;
                $product_promote_price = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

                /* 判断属性货品是否存在 */
                $prod = $this->goodsWarehouseService->getGoodsProductsProd($goods_id, $warehouse_id, $area_id, $area_city, $goods['model_attr']);

                //贡云商品 获取库存
                if ($goods['cloud_id'] > 0 && isset($products['product_id'])) {
                    $attr_number = !empty($attr_id) ? app(JigonManageService::class)->jigonGoodsNumber(['product_id' => $products['product_id']]) : 0;
                } else {
                    if ($goods['goods_type'] == 0) {
                        $attr_number = $goods['goods_number'];
                    } else {
                        if (empty($prod)) { //当商品没有属性库存时
                            $attr_number = $goods['goods_number'];
                        }
                    }
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $res['bar_code'] = $goods['bar_code'];
                } else {
                    $res['bar_code'] = $products['bar_code'] ?? '';
                }

                $attr_number = !empty($attr_number) ? $attr_number : 0;

                $res['attr_number'] = $attr_number;
                //ecmoban模板堂 --zhuo end

                $res['show_goods'] = 0;
                if ($goods_attr && $this->config['add_shop_price'] == 0) {
                    if (count($goods_attr) == count($attr_ajax['attr_id'])) {
                        $res['show_goods'] = 1;
                    }
                }

                $shop_price = $this->goodsCommonService->getFinalPrice($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city);
                $res['shop_price'] = price_format($shop_price);

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $web = app(CrossBorderService::class)->webExists();

                    if (!empty($web)) {
                        $res['goods_rate'] = $web->getGoodsRate($goods_id, $shop_price);
                        $res['formated_goods_rate'] = price_format($res['goods_rate']);
                    }
                }

                //属性价格
                if ($attr_id) {
                    $spec_price = $this->goodsCommonService->getFinalPrice($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city, 1, 0, 0, $res['show_goods'], $product_promote_price);
                } else {
                    $spec_price = 0;
                }

                /* 开启仓库地区模式 */
                if ($goods['model_price'] > 0 && empty($attr_id) && $shop_price <= 0) {
                    $time = $this->timeRepository->getGmTime();
                    //当前商品正在促销时间内
                    if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date'] && $goods['is_promote']) {
                        $shop_price = $goods['promote_price_org'];
                    } else {
                        $shop_price = $goods['shop_price'];
                    }
                }

                $res['goods_rank_prices'] = '';
                if ($this->config['add_shop_price'] == 0 && empty($spec_price) && empty($prod)) {
                    $spec_price = $shop_price;
                }

                if ($this->config['add_shop_price'] == 0) {
                    if ($attr_id) {
                        $res['result'] = price_format($spec_price);
                    } else {
                        $res['result'] = price_format($shop_price);
                    }
                    if ($products && $products['product_price'] > 0) {
                        $rank_prices = $this->goodsService->getUserRankPrices($goods_id, $products['product_price']);

                        if (!empty($rank_prices)) {
                            $this->smarty->assign('act', 'goods_rank_prices');
                            $this->smarty->assign('rank_prices', $rank_prices);
                            $res['goods_rank_prices'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
                        }
                    }
                } else {
                    $res['result'] = price_format($shop_price);
                }

                $res['spec_price'] = price_format($spec_price);
                $res['original_shop_price'] = $shop_price;
                $res['original_spec_price'] = $spec_price;
                $res['marketPrice_amount'] = price_format($goods['marketPrice'] + $spec_price);


                if ($this->config['add_shop_price'] == 0) {
                    $goods['marketPrice'] = isset($products['product_market_price']) && !empty($products['product_market_price']) ? $products['product_market_price'] : $goods['marketPrice'];
                    $res['result_market'] = price_format($goods['marketPrice']); // * $number
                } else {
                    $res['result_market'] = price_format($goods['marketPrice'] + $spec_price); // * $number
                }

                //@author-bylu 当点击了数量加减后 重新计算白条分期 每期的价格 start
                if ($goods['stages']) {
                    if (!is_array($goods['stages'])) {
                        $stages = unserialize($goods['stages']);
                    } else {
                        $stages = $goods['stages'];
                    }

                    $total = floatval(strip_tags(str_replace('¥', '', $res['result']))); //总价+运费*数量;
                    foreach ($stages as $K => $v) {
                        $res['stages'][$v] = round($total * ($goods['stages_rate'] / 100) + $total / $v, 2);
                    }
                }
                //@author-bylu 当点击了数量加减后 重新计算白条分期 每期的价格 end
            }

            $fittings_list = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city);

            if ($fittings_list) {
                $fittings_attr = $attr_id;

                $goods_fittings = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $area_city, '', 1, '', $fittings_attr);

                if (is_array($fittings_list)) {
                    foreach ($fittings_list as $vo) {
                        $fittings_index[$vo['group_id']] = $vo['group_id'];//关联数组
                    }
                }
                ksort($fittings_index);//重新排序

                $merge_fittings = get_merge_fittings_array($fittings_index, $fittings_list); //配件商品重新分组
                $fitts = get_fittings_array_list($merge_fittings, $goods_fittings);

                for ($i = 0; $i < count($fitts); $i++) {
                    $fittings_interval = $fitts[$i]['fittings_interval'];

                    $res['fittings_interval'][$i]['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                    $res['fittings_interval'][$i]['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                    if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                        $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                    } else {
                        $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                    }

                    $res['fittings_interval'][$i]['groupId'] = $fittings_interval['groupId'];
                }
            }


            if ($this->config['open_area_goods'] == 1) {
                $area_count = $this->goodsService->getHasLinkAreaGods($goods_id, $area_id, $area_city);
                if ($area_count < 1) {
                    $res['err_no'] = 2;
                }
            }
            //更新商品购买数量
            $start_date = $goods['xiangou_start_date'];
            $end_date = $goods['xiangou_end_date'];
            $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods_id, $user_id, '', $get_attr_id);
            $res['orderG_number'] = $order_goods['goods_number'];
            $res['type'] = $type;

            $limit = 1;
            $area_position_list = get_goods_user_area_position($goods['user_id'], $warehouse_id, $area_id, $area_city, $this->city_id, $get_attr_id, $goods_id, 0, 0, 1, 0, $limit);

            if (count($area_position_list) > 0) {
                $res['store_type'] = 1;
            } else {
                $res['store_type'] = 0;
            }

            return response()->json($res);
        }

        if (!empty($act) && $act == 'in_stock') {

            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];

            $area = $this->areaService->areaCookie();

            $goods_id = (int)request()->input('id', 0);
            $province = (int)request()->input('province', $area['province'] ?? 0);
            $city = (int)request()->input('city', $area['city'] ?? 0);
            $district = (int)request()->input('district', $area['district'] ?? 0);
            $d_null = (int)request()->input('d_null', 0);

            if (!empty($goods_id)) {
                $user_address = get_user_address_region($user_id);
                $user_address = $user_address && $user_address['region_address'] ? explode(",", $user_address['region_address']) : [];

                $street_info = Region::select('region_id')->where('parent_id', $district)->get();
                $street_info = $street_info ? $street_info->toArray() : [];
                $street_info = $street_info ? collect($street_info)->flatten()->all() : [];

                $street_list = 0;
                $this->street_id = 0;

                if ($street_info) {
                    $this->street_id = $street_info[0];
                    $street_list = implode(",", $street_info);
                }

                //清空
                $time = 60 * 24 * 30;
                cookie()->queue('type_province', 0, $time);
                cookie()->queue('type_city', 0, $time);
                cookie()->queue('type_district', 0, $time);

                $res['d_null'] = $d_null;

                if ($d_null == 0) {
                    if (in_array($district, $user_address)) {
                        $res['isRegion'] = 1;
                    } else {
                        $res['message'] = $GLOBALS['_LANG']['Distribution_message'];
                        $res['isRegion'] = 88; //原为0
                    }
                } else {
                    $district = '';
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
                    'street' => $this->street_id,
                    'street_area' => $street_list
                ];

                cache()->forever($area_cache_name, $area_cookie_cache);

                $res['goods_id'] = $goods_id;

                $flow_warehouse = get_warehouse_goods_region($province);
                cookie()->queue('flow_region', $flow_warehouse['region_id'] ?? 0, 60 * 24 * 30);
            }

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 切换仓库
        /*------------------------------------------------------ */
        if (!empty($act) && $act == 'in_warehouse') {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];
            $res['warehouse_type'] = addslashes(request()->input('warehouse_type', ''));

            $warehouse_cache_name = $this->areaService->getCacheName('warehouse_id');
            cache()->forever($warehouse_cache_name, $pid);

            /* 删除缓存 */
            $this->areaService->getCacheNameForget('area_info');

            $area_region = 0;
            cookie()->queue('area_region', $area_region, 60 * 24 * 30);

            $res['goods_id'] = $goods_id;

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 商品购买记录ajax处理
        /*------------------------------------------------------ */

        if (!empty($act) && $act == 'gotopage') {
            $res = ['err_msg' => '', 'result' => ''];

            $goods_id = (int)request()->input('id', 0);
            $page = (int)request()->input('page', 1);

            if (!empty($goods_id)) {
                $need_cache = $this->smarty->caching;
                $need_compile = $this->smarty->force_compile;

                $this->smarty->caching = false;
                $this->smarty->force_compile = true;

                /* 商品购买记录 */
                $where = [
                    'now' => $now,
                    'goods_id' => $goods_id
                ];

                $bought_notes = OrderGoods::select('goods_number')
                    ->whereHas('getOrder', function ($query) use ($where) {
                        $query = $query->where('main_count', 0);
                        $query->whereRaw("'" . $where['now'] . "' - oi.add_time < 2592000 AND og.goods_id = '" . $where['goods_id'] . "'");
                    });

                $bought_notes = $bought_notes->with([
                    'getOrder' => function ($query) {
                        $query->selectRaw("order_id, add_time, IF(order_status IN (2, 3, 4), 0, 1) AS order_status");
                    }
                ]);

                $start = (($page > 1) ? ($page - 1) : 0) * 5;
                if ($start > 0) {
                    $bought_notes = $bought_notes->skip($start);
                }

                $size = 5;
                if ($size > 0) {
                    $bought_notes = $bought_notes->take($size);
                }

                $bought_notes = $bought_notes->get();

                $bought_notes = $bought_notes ? $bought_notes->toArray() : [];

                if ($bought_notes) {
                    foreach ($bought_notes as $key => $val) {
                        $val = $val['get_order'] ? array_merge($val, $val['get_order']) : $val;
                        $val['add_time'] = local_date("Y-m-d H:i:s", $val['add_time']);

                        $val['user_name'] = Users::where('user_id', $val['user_id'])->value('user_name');

                        $bought_notes[$key] = $val;
                    }
                }

                $bought_notes = OrderGoods::select('goods_number')
                    ->whereHas('getOrder', function ($query) use ($where) {
                        $query = $query->where('main_count', 0);
                        $query->whereRaw("'" . $where['now'] . "' - oi.add_time < 2592000 AND og.goods_id = '" . $where['goods_id'] . "'");
                    });

                $count = $bought_notes->count();

                /* 商品购买记录分页样式 */
                $pager = [];
                $pager['page'] = $page;
                $pager['size'] = $size;
                $pager['record_count'] = $count;
                $pager['page_count'] = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
                $pager['page_first'] = "javascript:gotoBuyPage(1,$goods_id)";
                $pager['page_prev'] = $page > 1 ? "javascript:gotoBuyPage(" . ($page - 1) . ",$goods_id)" : 'javascript:;';
                $pager['page_next'] = $page < $page_count ? 'javascript:gotoBuyPage(' . ($page + 1) . ",$goods_id)" : 'javascript:;';
                $pager['page_last'] = $page < $page_count ? 'javascript:gotoBuyPage(' . $page_count . ",$goods_id)" : 'javascript:;';

                $this->smarty->assign('notes', $bought_notes);
                $this->smarty->assign('pager', $pager);


                $res['result'] = $this->smarty->fetch('library/bought_notes.lbi');

                $this->smarty->caching = $need_cache;
                $this->smarty->force_compile = $need_compile;
            }

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- PROCESSOR
        /*------------------------------------------------------ */

        $area = [
            'region_id' => $warehouse_id, //仓库ID
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'street_id' => $this->street_id,
            'street_list' => $this->street_list,
            'goods_id' => $goods_id,
            'user_id' => $user_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'merchant_id' => $goods['user_id'] ?? 0,
        ];

        $this->smarty->assign('area', $area);

        if (empty($goods) || (isset($goods['is_show']) && $goods['is_show'] == 0)) {
            /* 如果没有找到任何记录则跳回到首页 */
            return dsc_header("Location: ./\n");
        }

        assign_template('c');

        /* meta */
        $this->smarty->assign('keywords', !empty($goods['keywords']) ? htmlspecialchars($goods['keywords']) : htmlspecialchars($this->config['shop_keywords']));
        $this->smarty->assign('description', !empty($goods['goods_brief']) ? htmlspecialchars($goods['goods_brief']) : htmlspecialchars($this->config['shop_desc']));

        $position = assign_ur_here($goods['cat_id'], $goods['goods_name'], [], '', $goods['user_id']);

        /* current position */
        $this->smarty->assign('ur_here', $position['ur_here']);                  // 当前位置

        if ($goods['user_id'] == 0) {
            $this->smarty->assign('see_more_goods', 1);
        } else {
            $this->smarty->assign('see_more_goods', 0);
        }

        $this->smarty->assign('image_width', $this->config['image_width']);
        $this->smarty->assign('image_height', $this->config['image_height']);
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp()); // 网店帮助
        $this->smarty->assign('id', $goods_id);
        $this->smarty->assign('type', 0);
        $this->smarty->assign('cfg', $GLOBALS['_CFG']);

        $promotion = get_promotion_info($goods_id, $goods['user_id'], $goods);
        $this->smarty->assign('promotion', $promotion); //促销信息

        $consumption_count = 0;
        if ($goods['consumption']) {
            $consumption_count = 1;
        }

        $promo_count = count($promotion) + $consumption_count;
        $this->smarty->assign('promo_count', $promo_count); //促销数量

        //ecmoban模板堂 --zhuo start 限购
        $start_date = $goods['xiangou_start_date'];
        $end_date = $goods['xiangou_end_date'];

        if ($now > $start_date && $now < $end_date) {
            $xiangou = 1;
        } else {
            $xiangou = 0;
        }

        $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods_id, $user_id);
        $this->smarty->assign('xiangou', $xiangou);
        $this->smarty->assign('orderG_number', $order_goods['goods_number']);
        //ecmoban模板堂 --zhuo end 限购

        // 最小起订量
        if ($now > $goods['minimum_start_date'] && $now < $goods['minimum_end_date']) {
            $goods['is_minimum'] = 1;
        } else {
            $goods['is_minimum'] = 0;
        }

        //ecmoban模板堂 --zhuo start
        $shop_info = get_merchants_shop_info($goods['user_id']);
        $license_comp_adress = $shop_info['license_comp_adress'] ?? '';
        $adress = get_license_comp_adress($license_comp_adress);

        $this->smarty->assign('shop_info', $shop_info);
        $this->smarty->assign('adress', $adress);
        //ecmoban模板堂 --zhuo end

        //by wang 获得商品扩展信息
        $goods['goods_extends'] = get_goods_extends($goods_id);

        //判断是否支持退货服务
        $is_return_service = 0;
        if (isset($goods['return_type']) && $goods['return_type']) {
            $fruit1 = [1, 2, 3]; //退货，换货，仅退款
            $intersection = array_intersect($fruit1, $goods['return_type']); //判断商品是否设置退货相关
            if (!empty($intersection)) {
                $is_return_service = 1;
            }
        }
        //判断是否设置包退服务  如果设置了退换货标识，没有设置包退服务  那么修正包退服务为已选择
        if ($is_return_service == 1 && isset($data['goods_extends']['is_return']) && !$goods['goods_extends']['is_return']) {
            $goods['goods_extends']['is_return'] = 1;
        }

        $linked_goods = $this->goodsService->getLinkedGoods($goods_id, $warehouse_id, $area_id, $area_city);

        $goods['goods_style_name'] = $this->goodsCommonService->addStyle($goods['goods_name'], $goods['goods_name_style']);

        //商品标签 liu
        if ($goods['goods_tag'] && is_array($goods['goods_tag'])) {
            $goods['goods_tag'] = explode(',', $goods['goods_tag']);
        }

        /**
         * 店铺二维码
         */
        if ($goods['shopinfo']['ru_id'] > 0) {
            $shop_qrcode = $this->goodsService->getShopQrcode($goods['shopinfo']['ru_id']);
            $this->smarty->assign('shop_qrcode', $shop_qrcode);
        }

        // 商品二维码
        if ($this->config['two_code'] == 1) {
            $goods_qrcode = $this->goodsService->getGoodsQrcode($goods);
            $this->smarty->assign('weixin_img_url', $goods_qrcode['url']);
            $this->smarty->assign('weixin_img_text', trim($this->config['two_code_mouse']));
            $this->smarty->assign('two_code', $this->config['two_code']);
        }

        /*获取可用门店数量 by kong 20160721*/
        $goods['store_count'] = 0;

        $store_goods = OfflineStore::where('is_confirm', 1);
        $store_goods = $store_goods->whereHas('getStoreGoods', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
        });
        $store_goods = $store_goods->count();

        $store_products = StoreProducts::whereRaw(1);
        $store_products = $store_products->where('goods_id', $goods_id);
        $store_products = $store_products->whereHas('getOfflineStore', function ($query) {
            $query->where('is_confirm', 1);
        });

        $store_products = $store_products->count();

        if ($store_goods > 0 || $store_products > 0) {
            $goods['store_count'] = 1;
        }

        $this->smarty->assign('goods', $goods);

        $this->smarty->assign('goods_name', $goods['goods_name']);
        $this->smarty->assign('goods_id', $goods['goods_id']);
        $this->smarty->assign('promote_end_time', $goods['gmt_end_time']);

        //获得商品的规格和属性
        $properties = $this->goodsAttrService->getGoodsProperties($goods_id, $warehouse_id, $area_id, $area_city);

        $this->smarty->assign('properties', $properties['pro']);                              // 商品规格
        $this->smarty->assign('specification', $properties['spe']);                              // 商品属性

        /**
         * Start
         *
         * 商品推荐
         * 【'best' ：精品, 'new' ：新品, 'hot'：热销】
         */
        $where = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        /* 最新商品 */
        $where['type'] = 'new';
        $new_goods = $this->goodsService->getRecommendGoods($where);

        /* 推荐商品 */
        $where['type'] = 'best';
        $best_goods = $this->goodsService->getRecommendGoods($where);

        /* 热卖商品 */
        $where['type'] = 'hot';
        $hot_goods = $this->goodsService->getRecommendGoods($where);

        $this->smarty->assign('new_goods', $new_goods);
        $this->smarty->assign('best_goods', $best_goods);
        $this->smarty->assign('hot_goods', $hot_goods);
        /* End */

        $this->smarty->assign('related_goods', $linked_goods);                                   // 关联商品
        $this->smarty->assign('goods_article_list', $this->goodsService->getLinkedArticles($goods_id));                  // 关联文章

        $rank_prices = $this->goodsService->getUserRankPrices($goods_id, $goods['shop_price_original']);
        $this->smarty->assign('rank_prices', $rank_prices);    // 会员等级价格

        $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($goods_id));                    // 商品相册

        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $this->smarty->assign('bought_goods', $this->goodsService->getAlsoBought($where));                      // 购买了该商品的用户还购买了哪些商品
        $this->smarty->assign('goods_rank', $this->goodsService->getGoodsRank($goods_id));                       // 商品的销售排名

        /**
         * 店铺分类
         */
        if ($goods['user_id']) {
            $goods_store_cat = $this->categoryService->getChildTreePro(0, 0, 'merchants_category', 0, $goods['user_id']);

            if ($goods_store_cat) {
                $goods_store_cat = array_values($goods_store_cat);
            }

            $this->smarty->assign('goods_store_cat', $goods_store_cat);
        }

        $group_count = get_group_goods_count($goods_id);
        if ($group_count) {

            //组合套餐名
            $comboTabIndex = get_cfg_group_goods();
            $this->smarty->assign('comboTab', $comboTabIndex);

            //组合套餐组
            $fittings_list = get_goods_fittings([$goods_id], $warehouse_id, $area_id, $area_city);

            $fittings_index = [];
            if (is_array($fittings_list)) {
                foreach ($fittings_list as $vo) {
                    $fittings_index[$vo['group_id']] = $vo['group_id']; //关联数组
                }
            }
            ksort($fittings_index); //重新排序
            $this->smarty->assign('fittings_tab_index', $fittings_index); //套餐数量

            $this->smarty->assign('fittings', $fittings_list);                   // 配件
        }

        //获取tag
        $tag_array = get_tags($goods_id);
        $this->smarty->assign('tags', $tag_array);                                       // 商品的标记

        //获取关联礼包
        $package_goods_list = $this->goodsService->getPackageGoodsList($goods['goods_id'], $warehouse_id, $area_id, $area_city);
        $this->smarty->assign('package_goods_list', $package_goods_list);    // 获取关联礼包

        assign_dynamic('goods');
        $volume_price_list = $this->goodsCommonService->getVolumePriceList($goods['goods_id'], 1, 1);
        $this->smarty->assign('volume_price_list', $volume_price_list);    // 商品优惠价格区间

        $discuss_list = get_discuss_all_list($goods_id, 0, 1, 10);
        $this->smarty->assign('discuss_list', $discuss_list);
        $this->smarty->assign('all_count', $discuss_list['record_count']);

        //同类其他品牌
        $goods_brand = $this->goodsService->getGoodsSimilarBrand($goods['cat_id']);
        $this->smarty->assign('goods_brand', $goods_brand);

        //相关分类
        $goods_related_cat = $this->goodsService->getGoodsRelatedCat($goods['cat_id']);
        $this->smarty->assign('goods_related_cat', $goods_related_cat);

        //评分 start
        $comment_all = $this->commentService->getCommentsPercent($goods_id);
        if ($goods['user_id'] > 0) {
            $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods['user_id']); //商家所有商品评分类型汇总
            $this->smarty->assign('merch_cmt', $merchants_goods_comment);
        }
        //评分 end

        $this->smarty->assign('comment_all', $comment_all);

        $goods_area = 1;
        if ($this->config['open_area_goods'] == 1) {
            $area_count = $this->goodsService->getHasLinkAreaGods($goods_id, $area_id, $area_city);
            if ($area_count > 0) {
                $goods_area = 1;
            } else {
                $goods_area = 0;
            }
        }

        $this->smarty->assign('goods_area', $goods_area);

        //默认统一客服
        if ($this->config['customer_service'] == 0) {
            $shop_information = $this->merchantCommonService->getShopName($goods['user_id']);
            $this->smarty->assign('shop_close', $shop_information['shop_close']);

            $goods['user_id'] = 0;
        }

        $basic_info = get_shop_info_content($goods['user_id']);

        /*  @author-bylu 判断当前商家是否允许"在线客服" start */
        $shop_information = $this->merchantCommonService->getShopName($goods['user_id']);

        if ($shop_information) {
            //判断当前商家是平台,还是入驻商家 bylu
            if ($goods['user_id'] == 0) {
                //判断平台是否开启了IM在线客服
                $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                if ($kf_im_switch) {
                    $shop_information['is_dsc'] = true;
                } else {
                    $shop_information['is_dsc'] = false;
                }
            } else {
                $shop_information['is_dsc'] = false;
            }

            $this->smarty->assign('shop_information', $shop_information);

            if ($this->config['customer_service'] == 1) {
                $this->smarty->assign('shop_close', $shop_information['shop_close']);
            }
        }

        $this->smarty->assign('im_user_id', 'dsc' . $user_id); //登入用户ID;
        /*  @author-bylu  end */

        if ($basic_info) {
            $this->smarty->assign('kf_appkey', $basic_info['kf_appkey']); //应用appkey;

            $basic_info['province'] = Region::where('region_id', $basic_info['province'])->value('region_name');
            $basic_info['province'] = $basic_info['province'] ? $basic_info['province'] : '';

            $basic_info['city'] = Region::where('region_id', $basic_info['city'])->value('region_name');
            $basic_info['city'] = $basic_info['city'] ? $basic_info['city'] : '';

            $this->smarty->assign('basic_info', $basic_info);
        }

        if ($rank = get_rank_info()) {
            $this->smarty->assign('rank_name', $rank['rank_name']);
        }

        $this->smarty->assign('info', $this->userCommonService->getUserDefault($user_id));

        //@author-bylu 获取当前商品白条分期数据 start
        if ($goods['stages']) {
            //计算每期价格[默认,当js失效商品详情页才会显示这里的结果](每期价格=((总价+运费)*费率)+((总价+运费)/期数));
            $stages_arr = [];
            foreach ($goods['stages'] as $k => $v) {
                $stages_arr[$v]['stages_one_price'] = round(($goods['shop_price']) * ($goods['stages_rate'] / 100) + ($goods['shop_price']) / $v, 2);
            }
            $this->smarty->assign('stages', $stages_arr);
        }
        //@author-bylu  end

        //@author-bylu 获取当前商品可使用的优惠券信息 start
        $goods_coupons = get_new_coup($user_id, $goods_id, $goods['user_id']);
        $this->smarty->assign('goods_coupons', $goods_coupons);
        //@author-bylu  end

        $this->smarty->assign('extend_info', get_goods_extend_info($goods_id)); //扩展信息 by wu

        $this->smarty->assign('goods_id', $goods_id); //商品ID
        $this->smarty->assign('region_id', $warehouse_id); //商品仓库region_id
        $this->smarty->assign('user_id', $user_id);
        $this->smarty->assign('area_id', $area_id); //地区ID
        $this->smarty->assign('area_city', $area_city); //市级地区ID

        $site_http = $this->dsc->http();
        if ($site_http == 'http://') {
            $is_http = 1;
        } elseif ($site_http == 'https://') {
            $is_http = 2;
        } else {
            $is_http = 0;
        }

        $this->smarty->assign('url', url('/') . '/');
        $this->smarty->assign('is_http', $is_http);


        $this->smarty->assign('freight_model', $this->config['freight_model']); //ecmoban模板堂 --zhuo 运费模式
        $this->smarty->assign('one_step_buy', session('one_step_buy', 0)); //ecmoban模板堂 --zhuo 一步购物
        $this->smarty->assign('now_time', $now);           // 当前系统时间

        //获取seo start
        $seo = get_seo_words('goods');

        if ($seo) {
            foreach ($seo as $key => $value) {
                $seo[$key] = str_replace(['{sitename}', '{key}', '{name}', '{description}'], [$this->config['shop_name'], $goods['keywords'], $goods['goods_name'], $goods['goods_brief']], $value);
            }
        }

        if (isset($seo['keywords']) && !empty($seo['keywords'])) {
            $this->smarty->assign('keywords', htmlspecialchars($seo['keywords']));
        } else {
            $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
        }

        if (isset($seo['description']) && !empty($seo['description'])) {
            $this->smarty->assign('description', htmlspecialchars($seo['description']));
        } else {
            $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
        }

        if (isset($seo['title']) && !empty($seo['title'])) {
            $this->smarty->assign('page_title', htmlspecialchars($seo['title']));
        } else {
            $this->smarty->assign('page_title', $position['title']);
        }
        //获取seo end

        $this->smarty->assign('area_htmlType', 'goods');

        $ecsCookie = request()->cookie('ECS');

        /* 记录浏览历史 */
        if (isset($ecsCookie['history']) && !empty($ecsCookie['history'])) {
            $history = explode(',', $ecsCookie['history']);

            array_unshift($history, $goods_id);
            $history = array_unique($history);

            while (count($history) > $this->config['history_number']) {
                array_pop($history);
            }

            cookie()->queue('ECS[history]', implode(',', $history), 60 * 24 * 30);
        } else {
            cookie()->queue('ECS[history]', $goods_id, 60 * 24 * 30);
        }

        /* 更新点击次数 */
        Goods::where('goods_id', $goods_id)->increment('click_count', 1);

        $ecsCookie = request()->cookie('ECS');

        /* 浏览历史列表 */
        if (isset($ecsCookie['list_history']) && !empty($ecsCookie['list_history'])) {
            $list_history = explode(',', $ecsCookie['list_history']);

            array_unshift($list_history, $goods_id);
            $list_history = array_unique($list_history);

            while (count($list_history) > 100) {
                array_pop($list_history);
            }

            $list_history = implode(',', $list_history);
            cookie()->queue('ECS[list_history]', $list_history, 60 * 24 * 30);
        } else {
            cookie()->queue('ECS[list_history]', $goods_id, 60 * 24 * 30);
        }

        session([
            'goods_equal' => ''
        ]);

        /* 删除配件 start */
        $res = CartCombo::where(function ($query) use ($goods_id) {
            $query->where('parent_id', 0)
                ->where('goods_id', $goods_id)
                ->orWhere('parent_id', $goods_id);
        });

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }

        $res->delete();
        /* 删除配件 end */

        return $this->smarty->display('goods.dwt');
    }
}
