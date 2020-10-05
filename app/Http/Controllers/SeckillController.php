<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SeckillGoodsRemind;
use App\Models\SellerShopinfo;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\SeckillService;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;

/**
 * 秒杀商品前台文件
 */
class SeckillController extends InitController
{
    protected $seckillService;
    protected $goodsService;
    protected $dscRepository;
    protected $timeRepository;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $commentService;
    protected $goodsGalleryService;
    protected $sessionRepository;
    protected $config;
    protected $articleCommonService;
    protected $orderGoodsService;
    protected $cartCommonService;
    protected $cartRepository;

    public function __construct(
        SeckillService $seckillService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        GoodsGalleryService $goodsGalleryService,
        SessionRepository $sessionRepository,
        ArticleCommonService $articleCommonService,
        OrderGoodsService $orderGoodsService,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository
    )
    {
        $this->seckillService = $seckillService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->orderGoodsService = $orderGoodsService;
        $this->cartCommonService = $cartCommonService;
        $this->cartRepository = $cartRepository;
    }

    public function index()
    {
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

        if (request()->exists('keywords')) {
            clear_all_files();
        }


        $user_id = session('user_id', 0);
        $cat_id = (int)request()->input('cat_id', 0);

        /*------------------------------------------------------ */
        //-- act 操作项的初始化
        /*------------------------------------------------------ */
        $act = addslashes(request()->input('act', 'list'));
        $act = $act ? $act : 'list';
        /*------------------------------------------------------ */
        //-- 秒杀商品 --> 秒杀活动商品列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            assign_template();

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/seckill');
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            $position = assign_ur_here('seckill');
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            $categories_pro = get_top_category_tree();
            $this->smarty->assign('categories_pro', $categories_pro);

            $seckill_list = seckill_goods_list();

            $guess_goods = [];
            $will_begin = [];
            if ($seckill_list) {
                foreach ($seckill_list as $k => $v) {
                    $seckill_list[$k]['begin_time_formated'] = local_date('Y-m-d H:i:s', $v['begin_time']);
                    $seckill_list[$k]['end_time_formated'] = local_date('Y-m-d H:i:s', $v['end_time']);
                    if ($v['status'] == true) {
                        $guess_goods = $v['goods'] ?? [];
                    }
                    if ($v['soon'] == true) {
                        $will_begin = $v['goods'] ?? [];
                    }
                }
            }

            if ($guess_goods) {
                //更多好货根据商品销量排序
                $arrSort = [];
                foreach ($guess_goods as $uniqid => $row) {
                    foreach ($row as $key => $value) {
                        $arrSort[$key][$uniqid] = $value;
                    }
                }
                array_multisort($arrSort['percent'], SORT_DESC, $guess_goods);
            }

            $this->smarty->assign('seckill_list', $seckill_list);

            if ($cat_id) {
                $cat_info = Category::catInfo($cat_id)->first();
                $cat_info = $cat_info ? $cat_info->toArray() : [];

                $this->smarty->assign('cat_alias_name', $cat_info['cat_alias_name']);
                $this->smarty->assign('will_begin', $will_begin);
                return $this->smarty->display('seckill_cat_list.dwt');
            } else {
                //广告
                $seckill_top_ad = '';
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $seckill_top_ad .= "'seckill_top_ad" . $i . ","; //秒杀列表页面广告
                }

                $this->smarty->assign('seckill_top_ad', $seckill_top_ad); //liu
                $this->smarty->assign('guess_goods', $guess_goods);
                return $this->smarty->display('seckill_list.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 秒杀商品 --> 商品详情
        /*------------------------------------------------------ */
        elseif ($act == 'view') {

            /* 取得参数：秒杀活动ID */
            $seckill_id = (int)request()->input('id', 0);
            if ($seckill_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 跳转H5 start */
            $Loaction = dsc_url('/#/seckill/detail?seckill_id=' . $seckill_id);
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            assign_template();

            /* 取得秒杀活动信息 */
            $seckillInfo = seckill_info($seckill_id);
            if (empty($seckillInfo)) {
                return dsc_header("Location: ./\n");
            }
            $goods_id = $seckillInfo['goods_id'];

            //秒杀结束直接跳到普通商品购买
            if ($seckillInfo['is_end'] && !$seckillInfo['status']) {
                $Location = $this->dscRepository->buildUri('goods', ['gid' => $seckillInfo['goods_id']], $seckillInfo['goods_name']);
                return dsc_header("Location: $Location\n");
            }

            if (!$seckillInfo) {
                return show_message($GLOBALS['_LANG']['now_not_snatch']);
            }

            $position = assign_ur_here($seckillInfo['cat_id'], $seckillInfo['goods_name'], [], '', $seckillInfo['user_id']);

            // 商品评论百分比
            $comment_all = $this->commentService->getCommentsPercent($goods_id);

            $start_date = $this->timeRepository->getLocalStrtoTime($seckillInfo['begin_time']);
            $end_date = $this->timeRepository->getLocalStrtoTime($seckillInfo['end_time']);
            $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods_id, $user_id, 'seckill');

            // 看了又看
            $look_top = $this->seckillService->getTopSeckillGoods('click_count');
            $this->smarty->assign('look_top', $look_top);

            if ($area_id == null) {
                $area_id = 0;
            }

            $basic_info = SellerShopinfo::where('ru_id', $seckillInfo['user_id']);
            $basic_info = $this->baseRepository->getToArrayFirst($basic_info);

            if ($basic_info) {
                $basic_info['province'] = Region::where('region_id', $basic_info['province'])->value('region_name');
                $basic_info['city'] = Region::where('region_id', $basic_info['city'])->value('region_name');
            }

            $chat = $this->dscRepository->chatQq($basic_info);
            $basic_info['kf_ww'] = $chat['kf_ww'];
            $basic_info['kf_qq'] = $chat['kf_qq'];

            // 店铺推荐
            $merchant_seckill = $this->seckillService->getMerchantSeckillGoods($seckillInfo['id'], $seckillInfo['user_id']);
            $this->smarty->assign('merchant_seckill_goods', $merchant_seckill);

            /* 判断当前商家是否允许"在线客服" begin  */
            $where = [
                'goods_id' => $goods_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goods_info = $this->goodsService->getGoodsInfo($where);
            if ($this->config['customer_service'] == 0) {
                $goods_info['user_id'] = 0;
            }

            $shop_information = $this->merchantCommonService->getShopName($goods_info['user_id']); //通过ru_id获取到店铺信息;

            //判断当前商家是平台,还是入驻商家
            if ($goods_info['user_id'] == 0) {
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
            $shop_information['goods_id'] = $goods_id;
            $this->smarty->assign('shop_information', $shop_information);
            /* end */

            $area = [
                'region_id' => $warehouse_id, //仓库ID
                'province_id' => $this->province_id,
                'city_id' => $this->city_id,
                'district_id' => $this->district_id,
                'goods_id' => $goods_id,
                'user_id' => $user_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                'merchant_id' => $seckillInfo['user_id'],
            ];

            $properties = $this->goodsAttrService->getGoodsProperties($goods_id, $warehouse_id, $area_id, $area_city);  // 获得商品的规格和属性
            $this->smarty->assign('cfg', $GLOBALS['_CFG']);                // 模板赋值
            $this->smarty->assign('properties', $properties['pro']);                              // 商品属性
            $this->smarty->assign('specification', $properties['spe']);                              // 商品规格

            //商品运费
            $region = [1, $this->province_id, $this->city_id, $this->district_id, $this->street_id];
            $shippingFee = goodsShippingFee($goods_id, $warehouse_id, $area_id, $area_city, $region, $seckillInfo['sec_price']);
            $this->smarty->assign('shippingFee', $shippingFee);

            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $ecsCookie = request()->cookie('ECS');

            /* 记录秒杀浏览历史 */
            if (isset($ecsCookie['sec_history']) && !empty($ecsCookie['sec_history'])) {
                $sec_history = explode(',', $ecsCookie['sec_history']);

                array_unshift($sec_history, $seckill_id);
                $sec_history = array_unique($sec_history);

                while (count($sec_history) > $this->config['history_number']) {
                    array_pop($sec_history);
                }

                cookie()->queue('ECS[sec_history]', implode(',', $sec_history), 60 * 24 * 30);

            } else {
                cookie()->queue('ECS[sec_history]', $seckill_id, 60 * 24 * 30);
            }

            //ecmoban模板堂 --zhuo start
            $shop_info = get_merchants_shop_info($goods_info['user_id']);
            $license_comp_adress = $shop_info ? $shop_info['license_comp_adress'] : '';
            $adress = get_license_comp_adress($license_comp_adress);

            $this->smarty->assign('shop_info', $shop_info);
            $this->smarty->assign('adress', $adress);
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('seckill_id', $seckill_id);
            $this->smarty->assign('id', $goods_id);
            $this->smarty->assign('type', 0);
            $this->smarty->assign('area', $area);
            $this->smarty->assign('orderG_number', $order_goods['goods_number']);
            $this->smarty->assign('comment_all', $comment_all);
            $this->smarty->assign('properties', $properties['pro']);                              // 商品属性
            $this->smarty->assign('goods', $seckillInfo);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('pictures', $this->goodsGalleryService->getGoodsGallery($goods_id));  // 商品相册
            $this->smarty->assign('comment_percent', comment_percent($goods_id));
            $this->smarty->assign('basic_info', $basic_info);
            $this->smarty->assign('region_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('extend_info', get_goods_extend_info($goods_id)); //扩展信息 by wu
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            return $this->smarty->display('seckill_goods.dwt');
        }

        /*------------------------------------------------------ */
        //-- 价格
        /*------------------------------------------------------ */
        elseif ($act == 'price') {
            $res = ['err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1];

            $seckill_id = (int)request()->input('id', 0);
            //仓库管理的地区ID
            $number = (int)request()->input('number', 1);
            //加载类型
            $type = (int)request()->input('type', 0);
            $goods = seckill_info($seckill_id);

            if ($seckill_id == 0) {
                $res['err_msg'] = $GLOBALS['_LANG']['err_change_attr'];
                $res['err_no'] = 1;
            } else {
                if ($number == 0) {
                    $res['qty'] = $number = 1;
                } else {
                    $res['qty'] = $number;
                }

                $res['attr_number'] = isset($goods['sec_num']) ? $goods['sec_num'] : 0;
            }

            $res['type'] = $type;

            return response()->json($res);
        }

        /*------------------------------------------------------ */
        //-- 秒杀商品 --> 购买
        /*------------------------------------------------------ */

        elseif ($act == 'buy') {

            /* 查询：判断是否登录 */
            if ($user_id <= 0) {
                return show_message($GLOBALS['_LANG']['gb_error_login'], '', '', 'error');
            }

            $warehouse_id = (int)request()->input('warehouse_id', 0);
            //仓库管理的地区ID
            $area_id = (int)request()->input('area_id', 0);

            /* 查询：取得参数：秒杀活动id */
            $sec_goods_id = (int)request()->input('sec_goods_id', 0);
            if ($sec_goods_id <= 0) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：取得数量 */
            $number = (int)request()->input('number', 1);
            $number = $number < 1 ? 1 : $number;

            /* 查询：取得秒杀活动信息 */
            $seckillInfo = seckill_info($sec_goods_id, $number);

            if (empty($seckillInfo)) {
                return dsc_header("Location: ./\n");
            }

            /* 查询：检查秒杀活动是否是进行中 */
            if (!$seckillInfo['status']) {
                return show_message($GLOBALS['_LANG']['gb_error_status'], '', '', 'error');
            }

            $goods_id = !empty($seckillInfo['goods_id']) ? $seckillInfo['goods_id'] : 0;

            if ($seckillInfo['model_attr'] == 1) {
                $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
            } elseif ($seckillInfo['model_attr'] == 2) {
                $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $prod = $prod->where('city_id', $area_city);
                }
            } else {
                $prod = Products::where('goods_id', $goods_id);
            }

            $prod = $this->baseRepository->getToArrayFirst($prod);

            /* 查询：取得规格 */
            $specs = htmlspecialchars(trim(request()->input('goods_spec', '')));

            /* 查询：查询规格名称和值，不考虑价格 */
            $goods_attr = $this->goodsService->getGoodsAttrList($specs);

            $start_date = $this->timeRepository->getLocalStrtoTime($seckillInfo['begin_time']);
            $end_date = $this->timeRepository->getLocalStrtoTime($seckillInfo['end_time']);
            $order_goods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $seckillInfo['goods_id'], $user_id, 'seckill');
            $order_goods['goods_number'] = $order_goods['goods_number'] ?? 0;

            /* 秒杀限购 start */
            $restrict_amount = $seckillInfo['sec_limit'];
            $order_number = $order_goods['goods_number'] + $number;

            if ($order_goods['goods_number'] > 0 && $order_goods['goods_number'] >= $restrict_amount) {
                return show_message($GLOBALS['_LANG']['js_languages']['common']['Already_buy'] . $order_goods['goods_number'] . $GLOBALS['_LANG']['js_languages']['common']['Already_buy_two']);
            } elseif ($order_goods['goods_number'] > 0 && $order_number > $restrict_amount) {
                $buy_num = $restrict_amount - $order_goods['goods_number'];
                return show_message($GLOBALS['_LANG']['js_languages']['common']['Already_buy'] . $buy_num . $GLOBALS['_LANG']['js_languages']['common']['jian']);
            } elseif ($number > $restrict_amount) {
                return show_message($GLOBALS['_LANG']['js_languages']['common']['Purchase_quantity']);
            }
            /* 秒杀限购 end */

            /* 更新：清空购物车中所有团购商品 */
            load_helper('order');

            $session_id = $this->sessionRepository->realCartMacIp();

            $sess = empty($user_id) ? $session_id : '';

            /* 更新：清空购物车中所有秒杀商品 */
            $this->cartCommonService->clearCart($user_id, CART_SECKILL_GOODS);

            /* 更新：加入购物车 */
            $goods_price = isset($seckillInfo['sec_price']) > 0 ? $seckillInfo['sec_price'] : $seckillInfo['shop_price'];
            $cart = [
                'user_id' => $user_id,
                'session_id' => $sess,
                'goods_id' => $seckillInfo['goods_id'],
                'product_id' => isset($prod['product_id']) ? $prod['product_id'] : 0,
                'goods_sn' => addslashes($seckillInfo['goods_sn']),
                'goods_name' => addslashes($seckillInfo['goods_name']),
                'market_price' => $seckillInfo['market_price'],
                'goods_price' => $goods_price,
                'goods_number' => $number,
                'goods_attr' => addslashes($goods_attr),
                'goods_attr_id' => $specs,
                //ecmoban模板堂 --zhuo start
                'ru_id' => $seckillInfo['user_id'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city,
                //ecmoban模板堂 --zhuo end
                'is_real' => $seckillInfo['is_real'],
                'extension_code' => 'seckill' . $seckillInfo['id'],
                'parent_id' => 0,
                'rec_type' => CART_SECKILL_GOODS,
                'is_shipping' => $seckillInfo['is_shipping'],
                'is_gift' => 0,
                'freight' => $seckillInfo['freight'],
                'tid' => $seckillInfo['tid'],
                'shipping_fee' => $seckillInfo['shipping_fee']
            ];

            $rec_id = Cart::insertGetId($cart);

            $this->cartRepository->pushCartValue($rec_id);

            /* 更新：记录购物流程类型：团购 */
            session([
                'flow_type' => CART_SECKILL_GOODS,
                'extension_code' => 'seckill',
                'extension_id' => $sec_goods_id,
                'browse_trace' => 'seckill' /* 进入收货人页面 */
            ]);

            return dsc_header("Location: flow.php?step=checkout\n");
        }

        /*------------------------------------------------------ */
        //-- 设置提醒秒杀商品
        /*------------------------------------------------------ */
        elseif ($act == 'collect') {
            $result = ['error' => 0, 'message' => '', 'url' => ''];

            $sid = (int)request()->input('sid', 0);
            $user_id = (int)request()->input('user_id', 0);

            if ($user_id) {
                /* 检查是否已经存在于用户提醒表 */
                $count = SeckillGoodsRemind::where('user_id', $user_id)->where('sec_goods_id', $sid)->count();

                if ($count > 0) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['remind_goods_existed'];
                    return response()->json($result);
                } else {
                    $time = gmtime();

                    $other = [
                        'user_id' => $user_id,
                        'sec_goods_id' => $sid,
                        'add_time' => $time
                    ];
                    $r_id = SeckillGoodsRemind::insertGetId($other);

                    if (!$r_id) {
                        $result['error'] = 1;
                        $result['message'] = $this->db->errorMsg();
                        return response()->json($result);
                    } else {
                        $result['error'] = 0;
                        $result['message'] = lang('seckill.remind_goods_success');
                        return response()->json($result);
                    }
                }
            } else {
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return response()->json($result);
            }
        }

        /*------------------------------------------------------ */
        //-- 取消提醒秒杀商品
        /*------------------------------------------------------ */
        elseif ($act == 'cancel') {
            $result = ['error' => 0, 'message' => '', 'url' => ''];
            $sid = (int)request()->input('sid', 0);
            $user_id = (int)request()->input('user_id', 0);

            $res = SeckillGoodsRemind::where('sec_goods_id', $sid)->where('user_id', $user_id)->delete();

            if ($res) {
                $result['error'] = 0;
                $result['message'] = lang('seckill.cancel_remind_success');
                return response()->json($result);
            }
        }
    }
}
