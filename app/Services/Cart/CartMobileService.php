<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartCombo;
use App\Models\Coupons;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\GroupGoods;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\StoreGoods;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\DiscountService;
use App\Services\Activity\PackageService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;
use App\Services\Package\PackageGoodsService;
use App\Services\Store\StoreService;
use App\Services\User\UserCommonService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * 商城商品订单
 * Class CrowdFund
 * @package App\Services
 */
class CartMobileService
{
    protected $goodsMobileService;
    protected $goodsAttrService;
    protected $commonService;
    protected $timeRepository;
    protected $packageService;
    protected $config;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $goodsWarehouseService;
    protected $sessionRepository;
    protected $userCommonService;
    protected $cartCommonService;
    protected $orderGoodsService;
    protected $packageGoodsService;
    protected $baseRepository;
    protected $merchantCommonService;
    protected $storeService;

    public function __construct(
        GoodsMobileService $goodsMobileService,
        GoodsAttrService $goodsAttrService,
        TimeRepository $timeRepository,
        PackageService $packageService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        GoodsWarehouseService $goodsWarehouseService,
        SessionRepository $sessionRepository,
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService,
        OrderGoodsService $orderGoodsService,
        PackageGoodsService $packageGoodsService,
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        StoreService $storeService
    )
    {
        $files = [
            'clips',
            'common',
            'time',
            'main',
            'order',
            'function',
            'base',
            'goods',
            'ecmoban'
        ];
        load_helper($files);
        $this->goodsMobileService = $goodsMobileService;
        $this->goodsAttrService = $goodsAttrService;
        $this->timeRepository = $timeRepository;
        $this->packageService = $packageService;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
        $this->orderGoodsService = $orderGoodsService;
        $this->packageGoodsService = $packageGoodsService;
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->storeService = $storeService;
    }

    /**
     * 添加商品到购物车
     *
     * @param int $uid
     * @param $goods_id 商品编号
     * @param int $num 商品数量
     * @param array $spec 规格值对应的id数组
     * @param int $parent 基本件
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $store_id
     * @param string $take_time
     * @param int $store_mobile
     * @param int $rec_type
     * @param string $stages_qishu
     * @return bool
     * @throws \Exception
     */
    public function addToCartMobile($uid = 0, $goods_id, $num = 1, $spec = [], $parent = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $store_id = 0, $take_time = '', $store_mobile = 0, $rec_type = CART_GENERAL_GOODS, $stages_qishu = '-1')
    {
        $store_id = isset($store_id) ? $store_id : 0;
        $_parent_id = $parent;
        $stages_qishu = isset($stages_qishu) ? $stages_qishu : -1;

        /* 取得商品信息 */
        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'uid' => $uid
        ];

        //门店商品加入购物车是先清除购物车
        if ($store_id > 0) {
            $this->cartCommonService->clearStoreGoods($uid);
            if ($rec_type != CART_OFFLINE_GOODS) {
                $msg['error'] = '1';
                $msg['msg'] = lang('cart.join_cart_failed');
                return $msg;
            }
        }

        //分期购清除购物车
        if ($stages_qishu > 0) {
            $this->clearQishuGoods($uid);
        }

        //清除立即购买的其他商品
        if ($rec_type == CART_ONESTEP_GOODS) {
            $this->cartCommonService->clearCart($uid, $rec_type);
        }

        $goods = $this->goodsMobileService->getGoodsInfo($where);
        if (empty($goods)) {
            return false;
        }

        /* 检查商品单品限购 start */
        $xiangou = $this->xiangou_checked($goods['goods_id'], $num, $uid, 0, $rec_type);
        if ($xiangou['error'] == 1) {
            $msg['error'] = '1';
            $msg['msg'] = sprintf(lang('cart.xiangou_num_beyond'), $xiangou['cart_number']);
            return $msg;
        } elseif ($xiangou['error'] == 2) {
            $msg['error'] = '1';
            $msg['msg'] = sprintf(lang('cart.xiangou_num_beyond_cumulative'), $goods['xiangou_num']);
            return $msg;
        }
        /* 检查商品单品限购 end */

        // 最小起订量
        if ($goods['is_minimum'] == 1) {
            if ($goods['minimum'] > $num) {
                $msg['error'] = '1';
                $msg['msg'] = sprintf(lang('cart.is_minimum_number'), $goods['minimum']);
                return $msg;
            }
        }

        /* 如果是门店一步购物，获取门店库存 */
        if ($store_id > 0 && $rec_type == CART_OFFLINE_GOODS) {
            $goods['goods_number'] = StoreGoods::where('goods_id', $goods_id)->where('store_id', $store_id)->value('goods_number');
        }

        /* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
        if ($parent > 0) {
            $cart = Cart::where('goods_id', $parent)
                ->where('extension_code', '<>', 'package_buy');

            if (!empty($uid)) {
                $cart = $cart->where('user_id', $uid);
            }

            $cart = $cart->count();

            if ($cart == 0) {
                return false;
            }
        }

        /* 是否正在销售 */
        if ($goods['is_on_sale'] == 0) {
            return false;
        }

        /* 不是配件时检查是否允许单独销售 */
        if (empty($parent) && $goods['is_alone_sale'] == 0) {
            return false;
        }

        /* 如果商品有规格则取规格商品信息 配件除外 */

        /* 商品仓库货品 */
        $prod = $this->goodsWarehouseService->getGoodsProductsProd($goods_id, $warehouse_id, $area_id, $area_city, $goods['model_attr'], $store_id);

        if ($this->goodsAttrService->is_spec($spec) && !empty($prod)) {
            $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city, $store_id);
        }

        if (empty($product_info)) {
            $product_info = ['product_number' => 0, 'product_id' => 0];
        }

        /* 检查：库存 */
        if ($this->config['use_storage'] == 1) {
            $is_product = 0;
            //商品存在规格 是货品
            if ($this->goodsAttrService->is_spec($spec) && !empty($prod)) {
                if (!empty($spec)) {
                    /* 取规格的货品库存 */
                    if ($num > $product_info['product_number']) {
                        $msg['error'] = '1';
                        $msg['msg'] = lang('cart.stock_goods_null');
                        return $msg;
                    }
                }
            } else {
                $is_product = 1;
            }

            if ($is_product == 1) {
                //检查：商品购买数量是否大于总库存
                if ($num > $goods['goods_number']) {
                    $msg['error'] = '1';
                    $msg['msg'] = lang('cart.number_greater_inventory');
                    return $msg;
                }
            }
        }

        /* 计算商品的促销价格 */
        $warehouse_area['warehouse_id'] = $warehouse_id;
        $warehouse_area['area_id'] = $area_id;

        $goods_price = $this->goodsMobileService->getFinalPrice($uid, $goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city);

        $spec_price = $this->goodsAttrService->specPrice($spec, $goods_id, $warehouse_area);
        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city);
        $goods_attr_id = isset($spec) ? join(',', $spec) : '';

        //加入购物车
        if ($uid) {
            $sess_id = $uid;
        } else {
            $sess_id = $this->sessionRepository->realCartMacIp();
        }

        $time = $this->timeRepository->getGmTime();

        /* 初始化要插入购物车的基本件数据 */
        $parent = [
            'user_id' => $uid,
            'session_id' => $sess_id,
            'goods_id' => $goods_id,
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'model_attr' => $goods['model_attr'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'ru_id' => $goods['user_id'],
            'extension_code' => !is_null($goods['extension_code']) ? $goods['extension_code'] : '',
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'rec_type' => $rec_type,
            'add_time' => $time,
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee'],
            'commission_rate' => $goods['commission_rate'],
            'store_id' => $rec_type == CART_OFFLINE_GOODS ? $store_id : 0,  //by kong 20160721 门店id(1.4.2更新门店购物类型)
            'store_mobile' => $store_mobile,
            'take_time' => $take_time,
            'cost_price' => $goods['cost_price']
        ];

        /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
        /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
        /* 受此优惠 */
        $res = GroupGoods::where('goods_id', $goods_id)
            ->where('goods_price', $goods_price)
            ->where('parent_id', $_parent_id)
            ->orderBy('goods_price');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $basic_list = [];
        if ($res) {
            foreach ($res as $row) {
                $basic_list[$row['parent_id']] = $row['goods_price'];
            }
        }


        /* 取得购物车中该商品每个基本件的数量 */
        $basic_count_list = [];
        if ($basic_list) {
            $res = Cart::selectRaw("goods_id, SUM(goods_number) AS count")
                ->where('parent_id')
                ->where('extension_code', '<>', 'package_buy');

            if (!empty($uid)) {
                $res = $res->where('user_id', $uid);
            } else {
                $res = $res->where('session_id', $sess_id);
            }

            $res = $res->whereIn('goods_id', array_keys($basic_list));

            $res = $res->groupBy('goods_id');

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    $basic_count_list[$row['goods_id']] = $row['count'];
                }
            }
        }

        /* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
        /* 一个基本件对应一个该商品配件 */
        if ($basic_count_list) {
            $res = Cart::selectRaw("parent_id, SUM(goods_number) AS count")
                ->where('parent_id')
                ->where('extension_code', '<>', 'package_buy');

            if (!empty($uid)) {
                $res = $res->where('user_id', $uid);
            } else {
                $res = $res->where('session_id', $sess_id);
            }

            $res = $res->whereIn('parent_id', array_keys($basic_count_list));

            $res = $res->groupBy('parent_id');

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    $basic_count_list[$row['parent_id']] -= $row['count'];
                }
            }
        }

        /* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
        if ($basic_list) {
            foreach ($basic_list as $parent_id => $fitting_price) {
                /* 如果已全部插入，退出 */
                if ($num <= 0) {
                    break;
                }

                /* 如果该基本件不再购物车中，执行下一个 */
                if (!isset($basic_count_list[$parent_id])) {
                    continue;
                }

                /* 如果该基本件的配件数量已满，执行下一个基本件 */
                if ($basic_count_list[$parent_id] <= 0) {
                    continue;
                }

                /* 作为该基本件的配件插入 */
                $parent['goods_price'] = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
                $parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
                $parent['parent_id'] = $parent_id;

                /* 添加 */
                Cart::insert($parent);

                /* 改变数量 */
                $num -= $parent['goods_number'];
            }
        }

        $new_rec_id = 0;
        $act_id = 0;

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {

            /* 检查该商品是否已经存在在购物车中 */
            $row = Cart::select('user_id', 'goods_number', 'stages_qishu', 'rec_id', 'extension_code', 'act_id', 'is_gift', 'goods_id', 'ru_id')
                ->where('goods_id', $goods_id)
                ->where('parent_id', 0)
                ->where('goods_attr', $goods_attr)
                ->where('extension_code', '<>', 'package_buy')
                ->where('rec_type', $rec_type)
                ->where('group_id', '');

            if ($store_id > 0) {
                $row = $row->where('store_id', $store_id);
            }
            if ($warehouse_id > 0) {
                $row = $row->where('warehouse_id', $warehouse_id);
            }

            if (!empty($uid)) {
                $row = $row->where('user_id', $uid);
            } else {
                $row = $row->where('session_id', $sess_id);
            }

            $row = $row->first();

            $row = $row ? $row->toArray() : [];

            //记录购物车ID
            $new_rec_id = $row['rec_id'] ?? 0;
            $act_id = $row['act_id'] ?? 0;

            /*立即购买*/
            if ($rec_type == CART_ONESTEP_GOODS) {
                $parent['goods_price'] = $goods_price;
                $parent['goods_number'] = $num;
                $parent['parent_id'] = 0;

                $parent['rec_type'] = $rec_type;

                $new_rec_id = Cart::insertGetId($parent);

                // 会员等级
                $user_rank = $this->userCommonService->getUserRankByUid($uid);
                $user_rank['rank_id'] = $user_rank['rank_id'] ?? 0;

                /* 计算折扣 */
                $discount = compute_discount(3, [$new_rec_id], 0, 0, $uid, $user_rank['rank_id'], $rec_type);

                if (isset($discount['favourable']['act_id']) && $discount['favourable']['act_id']) {
                    Cart::where('rec_id', $new_rec_id)->update(['act_id' => $discount['favourable']['act_id']]);
                }

                if ($new_rec_id) {
                    return true;
                } else {
                    return false;
                }
            }

            if ($row) { //如果购物车已经有此物品，则更新

                if (!($row['stages_qishu'] != '-1' && $stages_qishu != '-1') && !($row['stages_qishu'] != '-1' && $stages_qishu == '-1') && !($row['stages_qishu'] == '-1' && $stages_qishu != '-1')) {
                    $num += $row['goods_number']; //这里是普通商品,数量进行累加;bylu
                }
                /*  @author-bylu  end */

                if ($this->goodsAttrService->is_spec($spec) && !empty($prod)) {
                    $goods_storage = $product_info['product_number'];
                } else {
                    $goods_storage = $goods['goods_number'];
                }

                if ($this->config['use_storage'] == 0 || $num <= $goods_storage) {
                    $cartOther = [
                        'goods_number' => $num,
                        'stages_qishu' => $stages_qishu,
                        'goods_price' => $goods_price,
                        'commission_rate' => $goods['commission_rate'],
                        'area_id' => $area_id,
                        'freight' => $goods['freight'],
                        'tid' => $goods['tid']
                    ];

                    $res = Cart::where('goods_id', $goods_id)
                        ->where('parent_id', 0)
                        ->where('goods_attr', $goods_attr)
                        ->where('extension_code', '<>', 'package_buy')
                        ->where('rec_type', $rec_type)
                        ->where('group_id', 0);

                    if ($warehouse_id > 0) {
                        $res = $res->where('warehouse_id', $warehouse_id);
                    }

                    if (!empty($uid)) {
                        $res = $res->where('user_id', $uid);
                    } else {
                        $res = $res->where('session_id', $sess_id);
                    }

                    $res->update($cartOther);
                } else {
                    $msg['error'] = '1';
                    $msg['msg'] = lang('cart.stock_goods_null');
                    return $msg;
                }
            } else { //购物车没有此物品，则插入
                $parent['goods_price'] = max($goods_price, 0);
                $parent['goods_number'] = $num;
                $parent['parent_id'] = 0;

                //如果分期期数不为 -1,那么即为分期付款商品;
                $parent['stages_qishu'] = $stages_qishu;

                $new_rec_id = Cart::insertGetId($parent);
            }
        }

        // 更新购物车优惠活动 start
        $fav = [];
        $is_fav = 0;

        if ($act_id == 0) {
            if ($parent['extension_code'] != 'package_buy' && $parent['is_gift'] == 0) {
                $fav = $this->getFavourable($parent['user_id'], $parent['goods_id'], $parent['ru_id'], 0, true);

                if ($fav) {
                    $is_fav = 1;
                }
            }
        }

        if ($is_fav == 1 && $new_rec_id > 0) {
            $this->update_cart_goods_fav($parent['user_id'], $new_rec_id, $fav['act_id']);
        }
        // 更新购物车优惠活动 end

        return true;
    }

    /**
     * 添加超值礼包到购物车
     *
     * @param int $user_id
     * @param int $package_id
     * @param int $num
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return mixed
     * @throws \Exception
     */
    public function addPackageToCartMobile($user_id = 0, $package_id = 0, $num = 1, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {

        $lang = lang('flow');

        /* 取得礼包信息 */
        $package = $this->packageService->getPackageInfo($package_id);
        if (empty($package)) {
            $result['error'] = 1;
            $result['message'] = $lang['goods_not_exists'];
            return $result;
        }

        if (!is_numeric($num) || intval($num) <= 0) {
            $result['error'] = 1;
            $result['message'] = $lang['invalid_number'];
        }

        /* 是否正在销售 */
        if ($package['is_on_sale'] == 0) {
            $result['error'] = 1;
            $result['message'] = $lang['not_on_sale'];
            return $result;
        }

        /* 现有库存是否还能凑齐一个礼包 */
        if ($this->config['use_storage'] == '1' && $this->packageGoodsService->judgePackageStock($package_id)) {
            $result['error'] = 1;
            $result['message'] = $lang['shortage'];
            return $result;
        }

        //加入购物车
        if ($user_id) {
            $sess_id = $user_id;
        } else {
            $sess_id = $this->sessionRepository->realCartMacIp();
        }

        $time = $this->timeRepository->getGmTime();

        $parent = [
            'user_id' => $user_id,
            'session_id' => $sess_id,
            'goods_id' => $package_id,
            'goods_sn' => '',
            'goods_name' => addslashes($package['act_name']),
            'market_price' => $package['market_package'],
            'goods_price' => $package['package_price'],
            'goods_number' => $num,
            'goods_attr' => '',
            'goods_attr_id' => '',
            'warehouse_id' => $warehouse_id, // 仓库
            'area_id' => $area_id, // 仓库地区
            'area_city' => $area_city, // 仓库地区城市
            'ru_id' => $package['user_id'],
            'is_real' => $package['is_real'],
            'extension_code' => 'package_buy',
            'is_gift' => 0,
            'rec_type' => CART_GENERAL_GOODS,
            'add_time' => $time
        ];

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */
            $row = Cart::where('goods_id', $package_id)
                ->where('parent_id', 0)
                ->where('extension_code', 'package_buy')
                ->where('rec_type', CART_GENERAL_GOODS)
                ->where('stages_qishu', '-1')
                ->where('store_id', 0);

            $row = $user_id ? $row->where('user_id', $user_id)->first() : $row->where('session_id', $sess_id)->first();
            $row = $row ? $row->toArray() : [];

            if ($row) { //如果购物车已经有此物品，则更新
                $num += $row['goods_number'];
                if ($this->config['use_storage'] == 0 || $num > 0) {

                    Cart::where('user_id', $user_id)
                        ->where('goods_id', $package_id)
                        ->where('parent_id', 0)
                        ->where('extension_code', 'package_buy')
                        ->where('rec_type', CART_GENERAL_GOODS)
                        ->update(['goods_number' => $num]);

                    $result['error'] = 0;
                    $result['message'] = $lang['add_package_cart_success'];
                    return $result;
                } else {
                    $result['error'] = 1;
                    $result['message'] = $lang['shortage'];
                    return $result;
                }
            } else { //购物车没有此物品，则插入
                Cart::insertGetId($parent);
                $result['error'] = 0;
                $result['message'] = $lang['add_package_cart_success'];
                return $result;
            }
        }
    }

    /**
     * 添加优惠活动（赠品）到购物车
     *
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function addGiftCart($params)
    {
        $result = [
            'error' => 0,
            'message' => '',
        ];

        $select_gift = isset($params['select_gift']) ? $params['select_gift'] : '';  //选中赠品id

        $select_gift = !empty($select_gift) && !is_array($select_gift) ? explode(",", $select_gift) : $select_gift;

        /** 取得优惠活动信息 */
        $favourable = app(DiscountService::class)->activityInfo($params['act_id']);

        if (!empty($favourable)) {
            if ($favourable['act_type'] == FAT_GOODS) {
                $favourable['act_type_ext'] = round($favourable['act_type_ext']);
            }
        } else {
            $result['error'] = 1;
            $result['message'] = lang('cart.discount_null_exist');
            return $result;
        }

        /** 判断用户能否享受该优惠 */
        if (!$this->favourableAvailable($params['uid'], $favourable)) {
            $result['error'] = 1;
            $result['message'] = lang('cart.not_enjoy_discount');
            return $result;
        }

        /** 检查购物车中是否已有该优惠 */
        $cart_favourable = $this->cartFavourable($params['uid'], $params['ru_id']);

        if ($this->favourableUsed($favourable, $cart_favourable)) {
            $result['error'] = 1;
            $result['message'] = lang('cart.discount_has_cart');
            return $result;
        }

        /* 赠品（特惠品）优惠 */
        if ($favourable['act_type'] == FAT_GOODS) {
            /* 检查是否选择了赠品 */
            if (empty($params['select_gift'])) {
                $result['error'] = 1;
                $result['message'] = lang('cart.select_gift');
                return $result;
            }

            /* 检查是否已在购物车 */
            $gift_name = [];

            $goodsname = $this->getGiftCart($params['uid'], $select_gift, $params['act_id']);
            foreach ($goodsname as $key => $value) {
                $gift_name[$key] = $value['goods_name'];
            }
            if (!empty($gift_name)) {
                $result['error'] = 1;
                $result['message'] = sprintf(lang('cart.select_gift_has_cart'), join(',', $gift_name));
                return $result;
            }

            /* 检查数量是否超过上限 */
            $count = isset($cart_favourable[$params['act_id']]) ? $cart_favourable[$params['act_id']] : 0;
            if ($favourable['act_type_ext'] <= 0 || $count + count($select_gift) > $favourable['act_type_ext']) {
                $result['error'] = 1;
                $result['message'] = lang('cart.gift_number_upper_limit');
                return $result;
            }

            $success = false;

            /* 添加赠品到购物车 */
            foreach ($favourable['gift'] as $gift) {
                if (in_array($gift['id'], $select_gift)) {

                    $goods = Goods::where('goods_id', $gift['id'])->first();
                    $goods = $goods ? $goods->toArray() : [];

                    // 添加参数
                    $arguments = [
                        'goods_id' => $gift['id'],
                        'user_id' => $params['uid'],
                        'goods_sn' => $goods['goods_sn'],
                        'product_id' => empty($product['id']) ? '' : $product['id'],
                        'group_id' => '',
                        'goods_name' => $goods['goods_name'],
                        'market_price' => $goods['market_price'],
                        'goods_price' => $gift['price'],
                        'goods_number' => 1,
                        'goods_attr' => '',
                        'is_real' => $goods['is_real'],
                        'extension_code' => CART_GENERAL_GOODS,
                        'parent_id' => 0,
                        'rec_type' => 0,  // 普通商品
                        'is_gift' => $params['act_id'],
                        'is_shipping' => $goods['is_shipping'],
                        'can_handsel' => '',
                        'model_attr' => $goods['model_attr'],
                        'goods_attr_id' => '',
                        'ru_id' => $goods['user_id'],
                        'shopping_fee' => '',
                        'warehouse_id' => '',
                        'area_id' => '',
                        'add_time' => $this->timeRepository->getGmTime(),
                        'store_id' => '',
                        'freight' => $goods['freight'],
                        'tid' => $goods['tid'],
                        'shipping_fee' => $goods['shipping_fee'],
                        'store_mobile' => '',
                        'take_time' => '',
                        'is_checked' => 1,
                    ];
                    Cart::insertGetId($arguments);
                    $success = true;
                }
            }

            if ($success == true) {
                $result['act_id'] = $params['act_id'];
                $result['ru_id'] = $params['ru_id'];
                $result['message'] = lang('cart.is_join_cart');
                return $result;
            } else {
                $result['error'] = 1;
                $result['message'] = lang('cart.join_cart_failed');
                return $result;
            }
        }

        $result['error'] = 1;
        $result['message'] = lang('cart.join_cart_failed');

        return $result;
    }


    /**
     * 积分团购等添加商品到购物车
     *
     * @param $uid
     * @param $goods_id
     * @param int $num
     * @param array $spec
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $store_id
     * @param $rec_type
     * @return bool
     */
    public function addEspeciallyToCartMobile($uid, $goods_id, $num = 1, $spec = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $store_id = 0, $rec_type)
    {

        /* 取得商品信息 */
        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'uid' => $uid
        ];
        $goods = $this->goodsMobileService->getGoodsInfo($where);

        if (empty($goods)) {
            return false;
        }

        /* 是否正在销售 */
        if ($goods['is_on_sale'] == 0) {
            return false;
        }

        /* 如果商品有规格则取规格商品信息 配件除外 */

        /* 商品仓库货品 */
        $prod = $this->goodsWarehouseService->getGoodsProductsProd($goods_id, $warehouse_id, $area_id, $area_city, $goods['model_attr'], $store_id);

        if ($this->goodsAttrService->is_spec($spec) && !empty($prod)) {
            $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city, $store_id);
        }

        if (empty($product_info)) {
            $product_info = ['product_number' => 0, 'product_id' => 0];
        }

        /* 检查：库存 */
        if ($this->config['use_storage'] == 1) {
            $is_product = 0;
            //商品存在规格 是货品
            if (is_spec($spec) && !empty($prod)) {
                if (!empty($spec)) {
                    /* 取规格的货品库存 */
                    if ($num > $product_info['product_number']) {
                        return false;
                    }
                }
            } else {
                $is_product = 1;
            }

            if ($is_product == 1) {
                //检查：商品购买数量是否大于总库存
                if ($num > $goods['goods_number']) {
                    return false;
                }
            }
        }

        /* 计算商品的促销价格 */
        $warehouse_area['warehouse_id'] = $warehouse_id;
        $warehouse_area['area_id'] = $area_id;

        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city);

        $goods_attr_id = isset($spec) ? join(',', $spec) : '';

        /* 初始化要插入购物车的基本件数据 */
        $parent = [
            'user_id' => $uid,
            'goods_id' => $goods_id,
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['marketPrice'],
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'model_attr' => $goods['model_attr'],
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'ru_id' => $goods['user_id'],
            'extension_code' => $goods['extension_code'],
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'rec_type' => $rec_type,
            'add_time' => $this->timeRepository->getGmTime(),
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee'],
            'commission_rate' => $goods['commission_rate'],
            'store_id' => $store_id,  //by kong 20160721 门店id
        ];

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */

            $row = Cart::select('goods_number', 'stages_qishu', 'rec_id')
                ->where('goods_id', $goods_id)
                ->where('parent_id', 0)
                ->where('goods_attr', $goods_attr)
                ->where('extension_code', '<>', 'package_buy')
                ->where('rec_type', $rec_type)
                ->where('group_id', '');

            if ($store_id > 0) {
                $row = $row->where('store_id', $store_id);
            }

            if (!empty($uid)) {
                $row = $row->where('user_id', $uid);
            }

            $row = $row->first();

            $row = $row ? $row->toArray() : [];

            //购物车没有此物品，则插入
            $goods_price = $this->goodsMobileService->getFinalPrice($uid, $goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city);
            $parent['goods_price'] = max($goods_price, 0);
            $parent['goods_number'] = $num;
            $parent['parent_id'] = 0;

            //如果分期期数不为 -1,那么即为分期付款商品;bylu
            $parent['stages_qishu'] = -1;

            if ($row) {
                Cart::where('rec_id', $row['rec_id'])->update($parent);
            } else {
                Cart::insertGetId($parent);
            }
        }

        return true;
    }

    /**
     * 购物车商品
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getGoodsCartListMobile($where = [])
    {
        $user_id = isset($where['uid']) && !empty($where['uid']) ? intval($where['uid']) : 0;

        $user_rank = Users::where('user_id', $where['uid'])->value('user_rank');

        $where['rec_type'] = isset($where['rec_type']) ? $where['rec_type'] : CART_GENERAL_GOODS;

        $res = Cart::where('rec_type', $where['rec_type']);

        $sess = $this->sessionRepository->realCartMacIp();

        if (empty($sess)) {
            // 首次访问购物车，此时不允许显示购物车的商品
            $res = $res->where('user_id', -1);
        } else {
            if ($user_id > 0) {
                // 登录用户优先使用user_id条件筛选商品
                $res = $res->where('user_id', $user_id);
            } else {
                $res = $res->where('session_id', $sess);
            }
        }

        /* 附加查询条件 start */
        if (isset($where['rec_id']) && $where['rec_id']) {
            $where['rec_id'] = !is_array($where['rec_id']) ? explode(",", $where['rec_id']) : $where['rec_id'];

            $res = $res->whereIn('rec_id', $where['rec_id']);
        }

        if (isset($where['goods_id']) && $where['goods_id']) {
            $where['goods_id'] = !is_array($where['goods_id']) ? explode(",", $where['goods_id']) : $where['goods_id'];

            $res = $res->whereIn('goods_id', $where['goods_id']);
        }

        $where['stages_qishu'] = $where['stages_qishu'] ?? -1;
        $res = $res->where('stages_qishu', $where['stages_qishu']);

        $where['store_id'] = $where['store_id'] ?? 0;
        $res = $res->where('store_id', $where['store_id']);

        if (isset($where['extension_code'])) {
            $res = $res->where('extension_code', '<>', $where['extension_code']);
        }

        if (isset($where['parent_id'])) {
            $res = $res->where('parent_id', $where['parent_id']);
        }

        if (isset($where['is_gift'])) {
            $res = $res->where('is_gift', $where['is_gift']);
        }
        /* 附加查询条件 end */

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select(
                    'goods_id',
                    'goods_thumb',
                    'model_price',
                    'integral',
                    'user_id',
                    'is_on_sale',
                    'cat_id',
                    'brand_id',
                    'goods_number as product_number',
                    'model_attr',
                    'is_delete',
                    'is_minimum',
                    'minimum_start_date',
                    'minimum_end_date',
                    'minimum',
                    'free_rate'
                );
            },
            'getWarehouseGoodsList',
            'getWarehouseAreaGoodsList',
            'getProductsList',
            'getProductsWarehouseList',
            'getProductsAreaList'
        ]);

        $res = $res->withCount([
            'getCollectGoods as is_collect' => function ($query) use ($where) {
                $query->where('user_id', $where['uid']);
            }
        ]);

        if (isset($where['limit']) && $where['limit']) {
            $res = $res->take($where['limit']);
        }

        $res = $res->orderBy('parent_id', 'ASC')
            ->orderBy('rec_id', 'DESC')
            ->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            $time = $this->timeRepository->getGmTime();
            foreach ($res as $k => $v) {
                $v = collect($v)->merge($v['get_goods'])->except('get_goods')->all();

                $res[$k]['free_rate'] = $v['free_rate'] ?? 0;
                $res[$k]['product_number'] = $v['product_number'] ?? 0;
                if ($v['extension_code'] != 'package_buy') {
                    //重新查询商品的限购数量限制
                    $xiangou = $this->xiangou_checked($v['goods_id'], $v['goods_number'], $user_id, 1);
                    $res[$k]['xiangou_error'] = 0;
                    if ($xiangou['error'] == 1) {
                        $res[$k]['xiangou_error'] = 1;
                        $res[$k]['xiangou_can_buy_num'] = $xiangou['can_buy_num'] ?? 0;
                    } elseif ($xiangou['error'] == 2) {
                        $res[$k]['xiangou_error'] = 1;
                        $res[$k]['xiangou_can_buy_num'] = $xiangou['can_buy_num'] ?? 0;
                    }

                    // 最小起订量
                    if ($time >= $v['minimum_start_date'] && $time <= $v['minimum_end_date'] && $v['is_minimum']) {
                        $res[$k]['is_minimum'] = 1;
                        $res[$k]['minimum'] = $v['minimum'];
                    } else {
                        $res[$k]['is_minimum'] = 0;
                        $res[$k]['minimum'] = 0;
                    }

                    /* 商品仓库消费积分 start */
                    if (isset($v['model_price'])) {
                        if (isset($where['warehouse_id']) && $v['model_price'] == 1 && $v['get_warehouse_goods_list']) {

                            $warehouseWhere = [
                                'where' => [
                                    [
                                        'name' => 'region_id',
                                        'value' => $v['warehouse_id']
                                    ]
                                ]
                            ];

                            $warehouseGoods = $this->baseRepository->getArraySqlFirst($v['get_warehouse_goods_list'], $warehouseWhere);

                            $v['integral'] = $warehouseGoods['pay_integral'] ?? 0;
                        } elseif ($v['model_price'] == 2 && $v['get_warehouse_area_goods_list']) {

                            $warehouseAreaWhere = [
                                'where' => [
                                    [
                                        'name' => 'region_id',
                                        'value' => $v['area_id']
                                    ]
                                ]
                            ];

                            $warehouseAreaGoods = $this->baseRepository->getArraySqlFirst($v['get_warehouse_area_goods_list'], $warehouseAreaWhere);

                            $v['integral'] = $warehouseAreaGoods['pay_integral'] ?? 0;
                        }
                    }
                    /* 商品仓库消费积分 end */

                    // 获取库存
                    if (!empty($v['product_id'])) { // 属性库存

                        if ($v['model_attr'] == 1) {
                            $productsWarehouseWhere = [
                                'where' => [
                                    [
                                        'name' => 'product_id',
                                        'value' => $v['product_id']
                                    ]
                                ]
                            ];

                            $productsWarehouse = $this->baseRepository->getArraySqlFirst($v['get_products_warehouse_list'], $productsWarehouseWhere);
                            $res[$k]['product_number'] = $productsWarehouse['product_number'] ?? 0;

                        } elseif ($v['model_attr'] == 2) {
                            $productsAreaWhere = [
                                'where' => [
                                    [
                                        'name' => 'product_id',
                                        'value' => $v['product_id']
                                    ]
                                ]
                            ];

                            $productsArea = $this->baseRepository->getArraySqlFirst($v['get_products_area_list'], $productsAreaWhere);
                            $res[$k]['product_number'] = $productsArea['product_number'] ?? 0;
                        } else {
                            $productsWhere = [
                                'where' => [
                                    [
                                        'name' => 'product_id',
                                        'value' => $v['product_id']
                                    ]
                                ]
                            ];

                            $products = $this->baseRepository->getArraySqlFirst($v['get_products_list'], $productsWhere);

                            $res[$k]['product_number'] = $products['product_number'] ?? 0;
                        }
                    }

                    $attr_id = $v['goods_attr_id'];
                    if ($v['extension_code'] != 'package_buy' && $v['is_gift'] == 0 && $v['parent_id'] == 0) {
                        $goods_price = $this->goodsMobileService->getFinalPrice($res[$k]['user_id'], $v['goods_id'], $v['goods_number'], true, $attr_id, $v['warehouse_id'], $v['area_id'], $v['area_city']);

                        if ($v['goods_price'] != $goods_price) {
                            $this->cartCommonService->getUpdateCartPrice($goods_price, $v['rec_id']);

                            $v['goods_price'] = $goods_price;
                        }
                    }

                    $res[$k]['is_collect'] = $v['is_collect'] > 0 ? 1 : 0;
                } else {
                    $res[$k]['is_collect'] = 0;
                }

                //判断商品类型，如果是超值礼包则修改链接和缩略图
                if ($v['extension_code'] == 'package_buy') {
                    /* 取得礼包信息 */
                    $package = $this->packageService->getPackageInfo($v['goods_id']);

                    if (empty($package)) {
                        unset($res[$k]);
                        continue;
                    }
                    $v['goods_thumb'] = $package['activity_thumb'] ?? '';
                    $package_goods_list = $package['goods_list'] ?? [];
                    if ($package_goods_list) {
                        $res[$k]['product_number'] = min(array_column($package_goods_list, 'product_number'));
                    }
                    $res[$k]['package_goods_list'] = $package_goods_list;
                }

                $res[$k]['integral_total'] = isset($v['integral']) ? $v['integral'] * $v['goods_number'] : 0;

                $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                $res[$k]['cat_id'] = $v['cat_id'] ?? 0;
                $res[$k]['brand_id'] = $v['brand_id'] ?? 0;

                $res[$k]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($v['goods_name'], $this->config['goods_name_length']) : $v['goods_name'];
                $res[$k]['goods_number'] = $v['goods_number'];
                $res[$k]['goods_name'] = $v['goods_name'];
                $res[$k]['goods_price'] = $v['goods_price'];
                $res[$k]['goods_price_format'] = $this->dscRepository->getPriceFormat($v['goods_price']);
                $res[$k]['market_price_format'] = $this->dscRepository->getPriceFormat($v['market_price']);
                $res[$k]['warehouse_id'] = $v['warehouse_id'];
                $res[$k]['area_id'] = $v['area_id'];
                $res[$k]['rec_id'] = $v['rec_id'];
                $res[$k]['is_checked'] = ($v['is_checked'] == 1) ? 1 : 0;
                $res[$k]['extension_code'] = $v['extension_code'];
                $res[$k]['is_gift'] = $v['is_gift'];
                $res[$k]['parent_id'] = $v['parent_id'];
                $res[$k]['is_on_sale'] = isset($v['is_on_sale']) ? $v['is_on_sale'] : 1;
                $res[$k]['is_invalid'] = $v['is_invalid'];
                if (isset($v['is_delete']) && $v['is_delete'] == 1) {
                    $res[$k]['is_invalid'] = 1;
                }

                $activity = FavourableActivity::select('act_name', 'start_time', 'end_time', 'min_amount', 'act_type', 'act_type_ext', 'act_range_ext', 'user_rank')
                    ->where('act_range_ext', 'like', '%' . $v['goods_id'] . '%')
                    ->where('user_rank', 'like', '%' . $user_rank . '%')
                    ->where('min_amount', '<=', $v['goods_price'])
                    ->where('review_status', 3)
                    ->get();

                $activity = $activity ? $activity->toArray() : [];

                if ($activity) {
                    foreach ($activity as $key => $val) {
                        $activity[$key]['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity[$key]['start_time']);
                        $activity[$key]['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity[$key]['end_time']);
                    }
                }

                $res[$k]['activity'] = $activity;

                if ($v['ru_id'] > 0) {
                    $res[$k]['store_id'] = $v['ru_id'];
                    $res[$k]['store_name'] = $this->merchantCommonService->getShopName($v['ru_id'], 1);
                } else {
                    $res[$k]['store_id'] = 0;
                    $res[$k]['store_name'] = lang('common.self_run');
                }

                //门店自提 1.4.2新增
                $where_store = [
                    'goods_id' => $v['goods_id'],
                    'is_confirm' => 1,
                    'district' => $where['district_id'] ?? 0,
                ];
                $store_goods = $this->storeService->getStoreCount($where_store);
                $store_products = $this->storeService->getStoreProductCount($where_store);
                if ($store_goods > 0 || $store_products > 0) {
                    $res[$k]['store_count'] = 1;
                } else {
                    $res[$k]['store_count'] = 0;
                }

                $res[$k] = $this->baseRepository->getArrayExcept($res[$k], ['get_goods', 'get_warehouse_goods_list', 'get_warehouse_area_goods_list', 'get_products_list', 'get_products_warehouse_list', 'get_products_area_list']);
            }

            $result = [];
            foreach ($res as $key => $value) {
                $result[$value['store_id']][] = $value;
            }

            $ret = array();

            foreach ($result as $key => $value) {
                array_push($ret, $value);
            }

            foreach ($ret as $k => $v) {
                $arr[$k]['store_name'] = $v[0]['store_name'];
                $arr[$k]['store_id'] = $v[0]['store_id'];

                $checked = $this->baseRepository->getKeyPluck($v, 'is_checked');
                $checked = array_unique($checked);

                if (in_array(1, $checked)) {
                    $arr[$k]['checked'] = true;
                } else {
                    $arr[$k]['checked'] = false;
                }

                $arr[$k]['coupuns_num'] = $this->getCartRuCoupunsNum($arr[$k]['store_id']); // 店铺下优惠券数量
                $arr[$k]['user_id'] = $user_id;
                $arr[$k]['goods'] = $v;

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $cbec = app(CrossBorderService::class)->cbecExists();
                    if (!empty($cbec)) {
                        $arr[$k]['rate_arr'] = $cbec->get_rate_arr($arr);
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 获取店铺优惠券数量
     *
     * @param int $ru_id
     * @return mixed
     */
    private function getCartRuCoupunsNum($ru_id = 0)
    {
        $time = $this->timeRepository->getGmTime();

        $res = Coupons::where('review_status', 3)
            ->where('ru_id', $ru_id)
            ->where('cou_end_time', '>', $time)
            ->where(function ($query) {
                $query->orWhere('cou_type', VOUCHER_ALL)
                    ->orWhere('cou_type', VOUCHER_USER);
            });
        $count = $res->count();

        return $count;
    }


    /**
     * 对同一商家商品按照活动分组
     * @return mixed
     */
    public function getCartByFavourable($merchant_goods = [])
    {
        $list_array = array();
        foreach ($merchant_goods as $key => $row) { // 第一层 遍历商家

            //商家商品列表
            $user_cart_goods = isset($row['goods']) && !empty($row['goods']) ? $row['goods'] : [];

            // 商家发布的优惠活动
            $favourable_list = $this->favourable_list($row['user_id'], $row['store_id']);

            // 对优惠活动进行归类
            $sort_favourable = $this->sort_favourable($favourable_list);

            if ($user_cart_goods) {
                foreach ($user_cart_goods as $goods_key => $goods_row) {
                    // 第二层 遍历购物车中商家的商品
                    $goods_row['market_price_formated'] = $this->dscRepository->getPriceFormat($goods_row['market_price'], false);
                    $goods_row['goods_price_formated'] = $this->dscRepository->getPriceFormat($goods_row['goods_price'], false);
                    $goods_row['goods_thumb'] = $this->dscRepository->getImagePath($goods_row['goods_thumb']);
                    $goods_row['original_price'] = $goods_row['goods_price'] * $goods_row['goods_number'];

                    // 活动-全部商品
                    if (isset($sort_favourable['by_all']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                        foreach ($sort_favourable['by_all'] as $fav_key => $fav_row) {
                            if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                $mer_ids = true;
                                if ($fav_row['userFav_type'] == 1 || $mer_ids) {
                                    if ($goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) {// 活动商品
                                        // 活动商品
                                        if (isset($goods_row) && $goods_row) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                            // 活动类型
                                            switch ($fav_row['act_type']) {
                                                case 0:
                                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                                    break;
                                                case 1:
                                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                                    break;
                                                case 2:
                                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                                    $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                                    break;

                                                default:
                                                    break;
                                            }
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                            @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];

                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = $this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id']); // 购物车满足活动最低金额

                                            // 购物车中已选活动赠品数量
                                            $cart_favourable = $this->cartFavourable($row['user_id'], $goods_row['ru_id']);
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = $this->favourableUsed($fav_row, $cart_favourable);
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                            /* 检查购物车中是否已有该优惠 */

                                            // 活动赠品
                                            if ($fav_row['gift']) {
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                            }

                                            // new_list->活动id->act_goods_list
                                            if (empty($goods_row['act_id'])) {
                                                $goods_row['act_id'] = $fav_row['act_id'];
                                            }
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                                            unset($goods_row);

                                        }
                                    } else {
                                        // 赠品
                                        if (isset($goods_row) && $goods_row && $goods_row['is_gift'] == $fav_row['act_id']) {
                                            if ($this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id'])) {
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                                unset($goods_row);
                                            } else {
                                                $is_gift = Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->count();
                                                // 取消商品 删除活动对应的赠品
                                                if ($is_gift) {
                                                    Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->delete();
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    if ($this->config['region_store_enabled']) {
                                        // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                                        $merchant_goods[$key]['new_list'][0]['act_goods_list'][$goods_row['rec_id']] = $fav_row;
                                    }
                                }
                            }
                            continue; // 如果活动包含全部商品，跳出循环体
                        }
                    }
                    if (empty($goods_row)) {
                        continue;
                    }

                    // 活动-分类
                    if (isset($sort_favourable['by_category']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                        //优惠活动关联分类集合
                        $get_act_range_ext = app(DiscountService::class)->activityRangeExt($row['user_id'], $row['store_id'], 1); // 1表示优惠范围 按分类

                        $str_cat = '';
                        foreach ($get_act_range_ext as $id) {
                            /**
                             * 当前分类下的所有子分类
                             * 返回一维数组
                             */
                            $cat_keys = app(DiscountService::class)->arr_foreach(app(DiscountService::class)->catList(intval($id)));
                            if ($cat_keys) {
                                $str_cat .= implode(",", $cat_keys);
                            }
                        }
                        if ($str_cat) {
                            $list_array = explode(",", $str_cat);
                        }

                        $list_array = !empty($list_array) ? array_merge($get_act_range_ext, $list_array) : $get_act_range_ext;
                        $id_list = app(DiscountService::class)->arr_foreach($list_array);
                        $id_list = array_unique($id_list);
                        $cat_id = $goods_row['cat_id'] ?? 0; //购物车商品所属分类ID

                        // 优惠活动ID集合
                        $favourable_id_list = $this->getFavourableId($sort_favourable['by_category']);

                        // 判断商品或赠品 是否属于本优惠活动
                        if ((in_array($cat_id, $id_list) && $goods_row['is_gift'] == 0) || in_array($goods_row['is_gift'], $favourable_id_list)) {

                            foreach ($sort_favourable['by_category'] as $fav_key => $fav_row) {
                                if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                    //优惠活动关联分类集合
                                    $fav_act_range_ext = !empty($fav_row['act_range_ext']) ? explode(',', $fav_row['act_range_ext']) : [];
                                    foreach ($fav_act_range_ext as $id) {
                                        /**
                                         * 当前分类下的所有子分类
                                         * 返回一维数组
                                         */
                                        $cat_keys = app(DiscountService::class)->arr_foreach(app(DiscountService::class)->catList(intval($id)));
                                        $fav_act_range_ext = array_merge($fav_act_range_ext, $cat_keys);
                                    }

                                    if ($goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0 && in_array($cat_id, $fav_act_range_ext)) { // 活动商品
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                        // 活动类型
                                        switch ($fav_row['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }

                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = $this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id']); // 购物车满足活动最低金额
                                        // 购物车中已选活动赠品数量
                                        $cart_favourable = $this->cartFavourable($row['user_id'], $goods_row['ru_id']);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = $this->favourableUsed($fav_row, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($fav_row['gift']) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                        }

                                        // new_list->活动id->act_goods_list
                                        if (empty($goods_row['act_id'])) {
                                            $goods_row['act_id'] = $fav_row['act_id'];
                                        }
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                                        unset($goods_row);
                                    }

                                    if (isset($goods_row) && $goods_row && $goods_row['is_gift'] == $fav_row['act_id']) { // 赠品
                                        if ($this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id'])) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                            unset($goods_row);
                                        } else {
                                            $is_gift = Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->count();
                                            // 取消商品 删除活动对应的赠品
                                            if ($is_gift) {
                                                Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->delete();
                                            }
                                        }
                                    }
                                }
                                continue;
                            }
                        }
                    }
                    if (empty($goods_row)) {
                        continue;
                    }

                    // 活动-品牌
                    if (isset($sort_favourable['by_brand']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                        // 优惠活动 品牌集合
                        $get_act_range_ext = app(DiscountService::class)->activityRangeExt($row['user_id'], $row['store_id'], 2); // 2表示优惠范围 按品牌
                        $brand_id = $goods_row['brand_id'] ?? 0;

                        // 优惠活动ID集合
                        $favourable_id_list = $this->getFavourableId($sort_favourable['by_brand']);

                        // 是品牌活动的商品或者赠品
                        if ((in_array(trim($brand_id), $get_act_range_ext) && $goods_row['is_gift'] == 0) || in_array($goods_row['is_gift'], $favourable_id_list)) {
                            foreach ($sort_favourable['by_brand'] as $fav_key => $fav_row) {
                                $act_range_ext_str = ',' . $fav_row['act_range_ext'] . ',';
                                $brand_id_str = ',' . $brand_id . ',';

                                if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                    if ($goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0 && strstr($act_range_ext_str, trim($brand_id_str))) { // 活动商品
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                        // 活动类型
                                        switch ($fav_row['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }

                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = $this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id']); // 购物车满足活动最低金额
                                        // 购物车中已选活动赠品数量
                                        $cart_favourable = $this->cartFavourable($row['user_id'], $goods_row['ru_id']);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = $this->favourableUsed($fav_row, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($fav_row['gift']) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                        }

                                        // new_list->活动id->act_goods_list
                                        if (empty($goods_row['act_id'])) {
                                            $goods_row['act_id'] = $fav_row['act_id'];
                                        }

                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                                        unset($goods_row);
                                    }

                                    if (isset($goods_row) && $goods_row && $goods_row['is_gift'] == $fav_row['act_id']) { // 赠品
                                        if ($this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id'])) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                            unset($goods_row);
                                        } else {
                                            $is_gift = Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->count();
                                            // 取消商品 删除活动对应的赠品
                                            if ($is_gift) {
                                                Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->delete();
                                            }
                                        }
                                    }
                                }
                                continue;
                            }
                        }
                    }
                    if (empty($goods_row)) {
                        continue;
                    }

                    // 活动-部分商品
                    if (isset($sort_favourable['by_goods']) && $goods_row['extension_code'] != 'package_buy' && substr($goods_row['extension_code'], 0, 7) != 'seckill') {
                        $get_act_range_ext = app(DiscountService::class)->activityRangeExt($row['user_id'], $row['store_id'], 3);// 3表示优惠范围 按商品
                        // 优惠活动ID集合
                        $favourable_id_list = $this->getFavourableId($sort_favourable['by_goods']);
                        // 判断购物商品是否参加了活动  或者  该商品是赠品
                        $goods_id = $goods_row['goods_id'];

                        if (in_array($goods_row['goods_id'], $get_act_range_ext) || in_array($goods_row['is_gift'], $favourable_id_list)) {
                            foreach ($sort_favourable['by_goods'] as $fav_key => $fav_row) { // 第三层 遍历活动
                                $act_range_ext_str = ',' . $fav_row['act_range_ext'] . ','; // 优惠活动中的优惠商品
                                $goods_id_str = ',' . $goods_id . ',';

                                // 如果是活动商品
                                if (isset($goods_row) && $goods_row && ($goods_row['act_id'] == $fav_row['act_id'] || empty($goods_row['act_id']))) {
                                    if (strstr($act_range_ext_str, $goods_id_str) && $goods_row['is_gift'] == 0 && $goods_row['parent_id'] == 0) {
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_id'] = $fav_row['act_id'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_name'] = $fav_row['act_name'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type'] = $fav_row['act_type'];
                                        // 活动类型
                                        switch ($fav_row['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.With_a_gift');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.Full_reduction');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = number_format($fav_row['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_txt'] = lang('flow.discount');
                                                $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext_format'] = floatval($fav_row['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['min_amount'] = $fav_row['min_amount']; //金额下线
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_type_ext'] = intval($fav_row['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_fav_amount'] += $goods_row['subtotal'];
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['available'] = $this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id']); // 购物车满足活动最低金额

                                        // 购物车中已选活动赠品数量
                                        $cart_favourable = $this->cartFavourable($row['user_id'], $goods_row['ru_id']);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['favourable_used'] = $this->favourableUsed($fav_row, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['left_gift_num'] = intval($fav_row['act_type_ext']) - (empty($cart_favourable[$fav_row['act_id']]) ? 0 : intval($cart_favourable[$fav_row['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($fav_row['gift']) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_gift_list'] = $fav_row['gift'];
                                        }

                                        // new_list->活动id->act_goods_list
                                        if (empty($goods_row['act_id'])) {
                                            $goods_row['act_id'] = $fav_row['act_id'];
                                        }
                                        $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                                        unset($goods_row);
                                    }

                                    if (isset($goods_row) && $goods_row && $goods_row['is_gift'] == $fav_row['act_id']) {
                                        // 赠品
                                        if ($this->favourableAvailable($row['user_id'], $fav_row, array(), $goods_row['ru_id'])) {
                                            $merchant_goods[$key]['new_list'][$fav_row['act_id']]['act_cart_gift'][$goods_row['rec_id']] = $goods_row;
                                            unset($goods_row);
                                        } else {
                                            $is_gift = Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->count();
                                            // 取消商品 删除活动对应的赠品
                                            if ($is_gift) {
                                                Cart::where('user_id', $row['user_id'])->where('is_gift', $fav_row['act_id'])->delete();
                                            }
                                        }
                                    }
                                }
                                continue;
                            }
                        }
                    }
                    if (empty($goods_row)) {
                        continue;
                    }

                    if ($goods_row) {
                        //如果循环完所有的活动都没有匹配的 那该商品就没有参加活动
                        // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                        $merchant_goods[$key]['new_list'][0]['act_goods_list'][$goods_row['rec_id']] = $goods_row;
                    }
                }
            }
        }

        foreach ($merchant_goods as $key => $val) {
            if (!empty($val['new_list'])) {
                foreach ($val['new_list'] as $k => $v) {
                    $val['new_list'][$k]['act_goods_list'] = collect($v['act_goods_list'])->values()->all();
                    if (isset($v['act_cart_gift'])) {
                        $val['new_list'][$k]['act_cart_gift'] = collect($v['act_cart_gift'])->values()->all();
                    }
                }
                $merchant_goods[$key]['new_list'] = collect($val['new_list'])->values()->all();
            }
        }
        return $merchant_goods;
    }

    /**
     * 根据购物车判断是否可以享受某优惠活动
     *
     * @param $user_id
     * @param $favourable
     * @param array $act_sel_id
     * @param int $ru_id
     * @return bool
     * @throws \Exception
     */
    public function favourableAvailable($user_id, $favourable, $act_sel_id = array(), $ru_id = -1)
    {
        /* 会员等级是否符合 */
        $user_rank = $this->userCommonService->getUserRankByUid($user_id);
        if (!$user_rank) {
            $user_rank['rank_id'] = 0;
        }
        if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank['rank_id'] . ',') === false) {
            return false;
        }

        /* 优惠范围内的商品总额 */
        $amount = $this->cartFavourableAmount($user_id, $favourable, $act_sel_id, $ru_id);

        /* 金额上限为0表示没有上限 */
        return $amount >= $favourable['min_amount'] && ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
    }

    /**
     * 购物车中是否已经有某优惠
     *
     * @param $favourable
     * @param $cart_favourable
     * @return bool
     */
    public function favourableUsed($favourable, $cart_favourable)
    {
        if ($favourable['act_type'] == FAT_GOODS) {
            return isset($cart_favourable[$favourable['act_id']]) && $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] && $favourable['act_type_ext'] > 0;
        } else {
            return isset($cart_favourable[$favourable['act_id']]);
        }
    }

    /**
     * 取得某用户等级当前时间可以享受的优惠活动
     *
     * @param int $user_id
     * @param int $ru_id
     * @param array $act_sel_id
     * @return array
     * @throws \Exception
     */
    public function favourable_list($user_id = 0, $ru_id = 0, $act_sel_id = [])
    {
        // 商家优惠活动
        $list = app(DiscountService::class)->activityListAll($user_id, $ru_id);

        /* 购物车中已有的优惠活动及数量 */
        $used_list = $this->cartFavourable($user_id, $ru_id);

        $favourable_list = [];
        if ($list) {
            foreach ($list as $favourable) {
                $favourable['start_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $favourable['start_time']);
                $favourable['end_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $favourable['end_time']);
                $favourable['formated_min_amount'] = $this->dscRepository->getPriceFormat($favourable['min_amount']);
                $favourable['formated_max_amount'] = $this->dscRepository->getPriceFormat($favourable['max_amount']);
                $favourable['gift'] = unserialize($favourable['gift']);
                foreach ($favourable['gift'] as $key => $value) {
                    // 商品信息
                    $goods = Goods::select('goods_thumb')->where('goods_id', $value['id'])->first();
                    $goods = $goods ? $goods->toArray() : [];

                    $cart_gift_num = $this->goodsNumInCartGift($user_id, $value['id']);//赠品在购物车数量
                    if (!empty($goods)) {
                        $favourable['gift'][$key]['ru_id'] = $favourable['user_id'];
                        $favourable['gift'][$key]['act_id'] = $favourable['act_id'];
                        $favourable['gift'][$key]['formated_price'] = $this->dscRepository->getPriceFormat($value['price'], false);
                        // 赠品缩略图
                        $favourable['gift'][$key]['thumb_img'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
                        $favourable['gift'][$key]['is_checked'] = $cart_gift_num ? true : false;
                    } else {
                        unset($favourable['gift'][$key]);
                    }
                }

                //是否能享受
                $favourable['available'] = $this->favourableAvailable($user_id, $favourable, $act_sel_id);

                if ($favourable['available']) {
                    //是否尚未享受
                    $favourable['available'] = !$this->favourableUsed($favourable, $used_list);
                }

                $favourable_list[] = $favourable;
            }
        }
        return $favourable_list;
    }


    /**
     * 取得购物车中已有的优惠活动及数量
     *
     * @return array
     */
    public function cartFavourable($user_id, $ru_id = -1)
    {
        $prefix = Config::get('database.connections.mysql.prefix');
        $where = '';
        if ($ru_id > -1) {
            $where .= " AND ru_id = '$ru_id'";
        }

        $sql = "SELECT is_gift, COUNT(*) AS num " . "FROM {$prefix}cart  WHERE user_id = $user_id  AND rec_type = '" . CART_GENERAL_GOODS . "'" . " AND is_gift > 0 " . $where . " GROUP BY is_gift";
        $res = DB::select($sql);

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $row = get_object_vars($row);
                $list [$row ['is_gift']] = $row ['num'];
            }
        }
        return $list;
    }

    /**
     * 查询  购物车商赠品数量
     * @param $id  用户ID
     * @param $goods_id  用户ID
     * @return mixed
     */
    public function goodsNumInCartGift($id, $goods_id = 0)
    {
        $cart_list = Cart::where('user_id', $id)
            ->where('goods_id', $goods_id)
            ->where('is_gift', '>', 0)
            ->sum('goods_number');

        return $cart_list;
    }

    // 对优惠商品进行归类
    public function sort_favourable($favourable_list)
    {
        $arr = array();
        foreach ($favourable_list as $key => $value) {
            switch ($value['act_range']) {
                case FAR_ALL:
                    $arr['by_all'][$key] = $value;
                    break;
                case FAR_CATEGORY:
                    $arr['by_category'][$key] = $value;
                    break;
                case FAR_BRAND:
                    $arr['by_brand'][$key] = $value;
                    break;
                case FAR_GOODS:
                    $arr['by_goods'][$key] = $value;
                    break;
                default:
                    break;
            }
        }
        return $arr;
    }

    // 获取活动id数组
    public function getFavourableId($favourable)
    {
        $arr = array();
        foreach ($favourable as $key => $value) {
            $arr[$key] = $value['act_id'];
        }

        return $arr;
    }

    /**
     * 优惠范围内的商品总额
     */
    public function cartFavourableAmount($user_id, $favourable, $act_sel_id = array('act_sel_id' => '', 'act_pro_sel_id' => '', 'act_sel' => ''), $ru_id = -1)
    {
        $prefix = Config::get('database.connections.mysql.prefix');

        $fav_where = "";
        if ($favourable['userFav_type'] == 0) {
            $fav_where = " AND g.user_id = '" . $favourable['user_id'] . "' ";
        } else {
            if ($ru_id > -1) {
                $fav_where = " AND g.user_id = '$ru_id' ";
            }
        }
        if (!empty($act_sel_id['act_sel']) && ($act_sel_id['act_sel'] == 'cart_sel_flag')) {
            $sel_id_list = explode(',', $act_sel_id['act_sel_id']);
            $fav_where .= "AND c.rec_id " . db_create_in($sel_id_list);
        }
        //ecmoban模板堂 --zhuo end

        /* 查询优惠范围内商品总额的sql */
        $sql = "SELECT SUM(c.goods_price * c.goods_number) as goods_price " .
            " FROM " . $prefix . "cart AS c, " . $prefix . "goods AS g " .
            " WHERE c.goods_id = g.goods_id " .
            " AND c.user_id = $user_id AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
            " AND c.is_gift = 0 " .
            " AND c.is_checked = 1 " .
            " AND c.goods_id > 0 " . $fav_where; //ecmoban模板堂 --zhuo

        $id_list = [];
        $list_array = [];

        $amount = 0;
        if ($favourable) {
            /* 根据优惠范围修正sql */
            if ($favourable['act_range'] == FAR_ALL) {
                // sql do not change
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                /* 取得优惠范围分类的所有下级分类 */
                $cat_list = explode(',', $favourable['act_range_ext']);
                foreach ($cat_list as $id) {
                    $id_list = app(DiscountService::class)->arr_foreach(app(DiscountService::class)->catList($id));
                }
                $id_list = implode(',', $id_list);
                $sql .= "AND g.cat_id in ($id_list)";
            } elseif ($favourable['act_range'] == FAR_BRAND) {
                $id_list = $favourable['act_range_ext'];
                $sql .= "AND g.brand_id in ($id_list)";
            } elseif ($favourable['act_range'] == FAR_GOODS) {
                $id_list = $favourable['act_range_ext'];
                $sql .= "AND g.goods_id in ($id_list)";
            }

            /* 优惠范围内的商品总额 */
            $amount = DB::select($sql);

            $amount = get_object_vars($amount[0]);
        }

        return $amount['goods_price'];
    }

    /**
     * 检查是否已在购物车
     * @return mixed
     */
    public function getGiftCart($user_id = 0, $is_gift_cart = [], $act_id = 0)
    {
        $cart = Cart::select('goods_name')
            ->where('user_id', $user_id)
            ->wherein('goods_id', $is_gift_cart)
            ->where('is_gift', $act_id)
            ->where('rec_type', CART_GENERAL_GOODS)
            ->get()
            ->toArray();

        return $cart;
    }

    /**
     * 检查该项是否为基本件 以及是否存在配件
     * 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_idgoods_number为1
     *
     * @param array $where
     * @return  array
     */
    public function getOffersAccessoriesListMobile($where = [])
    {
        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = isset($uid) && !empty($uid) ? intval($uid) : 0;

        $res = Cart::select('goods_id');

        if (isset($where['rec_id'])) {
            $res = $res->where('rec_id', $where['rec_id']);
        }

        if (isset($where['extension_code'])) {
            if (is_array($where['extension_code'])) {
                $res = $res->where('extension_code', $where['extension_code'][0], $where['extension_code'][1]);
            } else {
                $res = $res->where('extension_code', $where['extension_code']);
            }
        }

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $res = $res->where('session_id', $session_id);
        }

        $where = [
            'user_id' => $user_id,
            'session_id' => $session_id
        ];
        $res = $res->whereHas('getCartParentGoods', function ($query) use ($where) {
            if (!empty($where['user_id'])) {
                $query->where('user_id', $where['user_id']);
            } else {
                $query->where('session_id', $where['session_id']);
            }
        });

        $res = $res->with(['getCartParentGoods']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $row['get_cart_parent_goods'] ? array_merge($row, $row['get_cart_parent_goods']) : $row;

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 删除购物车商品
     * @param $args
     * @return array
     */
    public function deleteCartGoods($args)
    {
        return Cart::where('rec_id', $args['rec_id'])
            ->where('user_id', $args['uid'])
            ->delete();
    }

    /**
     * 选择购物车商品
     * @param $uid
     * @param array $rec_id
     * @return mixed
     */
    public function checked($uid = 0, $rec_id = [])
    {
        if ($uid > 0) {
            $res = Cart::where('user_id', $uid)->update(['is_checked' => 0]);
            if (!empty($rec_id) && is_array($rec_id)) {
                $res = Cart::where('user_id', $uid)->whereIn('rec_id', $rec_id)->update(['is_checked' => 1]);
            }
            return $res;
        } else {
            $sess = $this->sessionRepository->realCartMacIp();
            $res = Cart::where('session_id', $sess)->update(['is_checked' => 0]);
            if (!empty($rec_id) && is_array($rec_id)) {
                $res = Cart::where('session_id', $sess)->whereIn('rec_id', $rec_id)->update(['is_checked' => 1]);
            }
            return $res;
        }

        return false;
    }

    /**
     * 更改购物车数量
     * @param int $num
     * @param $rec_id
     * @param $uid
     * @return bool
     */
    public function update($num = 0, $rec_id, $uid)
    {
        if ($num > 0) {
            $res = Cart::where('rec_id', $rec_id)
                ->where('user_id', $uid)
                ->update(['goods_number' => $num]);

            return $res;
        }

        return false;
    }

    /**
     * 更新购物车商品配件数量
     * @param int $num
     * @param string $group_id
     * @param int $parent_id
     * @param int $uid
     * @return bool
     */
    public function updateGroupNum($num = 0, $group_id = '', $parent_id = 0, $uid = 0)
    {
        if ($num > 0) {
            $cart = Cart::where('group_id', $group_id)
                ->where('parent_id', $parent_id);

            if (!empty($uid)) {
                $cart = $cart->where('user_id', $uid);
            } else {
                $real_ip = $this->sessionRepository->realCartMacIp();
                $cart = $cart->where('session_id', $real_ip);
            }
            $res = $cart->update(['goods_number' => $num]);

            return $res;
        }

        return false;
    }

    /**
     * 购物车商品
     * @param $user_id
     * @param int $type
     * @param string $cart_value
     * @param bool $is_checked
     * @return array
     */
    public function getCartGoods($user_id, $type = CART_GENERAL_GOODS, $cart_value = '', $is_checked = true)
    {
        $sess = $this->sessionRepository->realCartMacIp();

        $time = $this->timeRepository->getGmTime();

        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;

        $res = Cart::where('rec_type', $type)
            ->where('stages_qishu', '-1')
            ->where('store_id', 0);

        if ($user_id) {
            $res = $res->where('user_id', $user_id);
            Cart::where('session_id', $sess)->update(['user_id' => $user_id, 'session_id' => 0]);
        } else {
            $res = $res->where('session_id', $sess);
        }
        if ($is_checked) {
            $res = $res->where('is_checked', 1);
        }
        if (isset($cart_value) && $cart_value) {
            $res = $res->whereIn('rec_id', $cart_value);
        }

        $res = $res->with([
            'getGoods' => function ($query) {
                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $query = $query->select('goods_id', 'goods_thumb', 'model_price', 'integral', 'user_id', 'is_on_sale', 'cat_id', 'brand_id', 'free_rate', 'is_minimum', 'minimum', 'minimum_start_date', 'minimum_end_date');
                } else {
                    $query = $query->select('goods_id', 'goods_thumb', 'model_price', 'integral', 'user_id', 'is_on_sale', 'cat_id', 'brand_id', 'is_minimum', 'minimum', 'minimum_start_date', 'minimum_end_date');
                }

                $query->with([
                    'getGoodsConsumption'
                ]);
            }
        ]);

        $res = $res->orderBy('parent_id', 'ASC')
            ->orderBy('rec_id', 'DESC');

        if (!$is_checked) {
            $res = $res->groupBy('act_id');
        }
        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if (empty($res)) {
            return [];
        }

        foreach ($res as $k => $v) {

            $goods = $v['get_goods'] ?? [];

            $v = $v['get_goods'] ? array_merge($v, $v['get_goods']) : $v;
            //判断商品类型，如果是超值礼包则修改链接和缩略图
            if ($v['extension_code'] == 'package_buy') {
                /* 取得礼包信息 */
                $package = $this->packageService->getPackageInfo($v['goods_id']);
                $v['goods_thumb'] = $package['activity_thumb'];
                $res[$k]['package_goods_list'] = $package['goods_list'];
            }
            $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
            $res[$k]['cat_id'] = $v['cat_id'] ?? 0;
            $res[$k]['brand_id'] = $v['brand_id'] ?? 0;
            $res[$k]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($v['goods_name'], $this->config['goods_name_length']) : $v['goods_name'];

            // 当前商品正在最小起订量
            if ($v['extension_code'] != 'package_buy') {
                if ($time >= $v['minimum_start_date'] && $time <= $v['minimum_end_date'] && $v['is_minimum']) {
                    $v['is_minimum'] = 1;
                } else {
                    $v['is_minimum'] = 0;
                }
            }

            if (!empty($v['is_minimum']) && $v['is_minimum'] == 1) {
                $v['goods_number'] = $v['minimum'];
            }

            $res[$k]['goods_number'] = $v['goods_number'];
            $res[$k]['goods_name'] = $v['goods_name'];
            $res[$k]['market_price_format'] = $this->dscRepository->getPriceFormat($v['market_price']);
            $res[$k]['warehouse_id'] = $v['warehouse_id'];
            $res[$k]['area_id'] = $v['area_id'];
            $res[$k]['rec_id'] = $v['rec_id'];
            $res[$k]['is_checked'] = ($v['is_checked'] == 1) ? true : false;
            $res[$k]['extension_code'] = $v['extension_code'];
            $res[$k]['is_gift'] = $v['is_gift'];
            $res[$k]['is_on_sale'] = isset($v['is_on_sale']) ? $v['is_on_sale'] : 1;

            $attr_id = $v['goods_attr_id'];
            if ($v['extension_code'] != 'package_buy' && $v['is_gift'] == 0 && $v['parent_id'] == 0) {
                $goods_price = $this->goodsMobileService->getFinalPrice($res[$k]['user_id'], $v['goods_id'], $v['goods_number'], true, $attr_id, $v['warehouse_id'], $v['area_id'], $v['area_city']);

                if ($v['goods_price'] != $goods_price) {
                    $this->cartCommonService->getUpdateCartPrice($goods_price, $v['rec_id']);

                    $v['goods_price'] = $goods_price;
                }
            }

            $res[$k]['goods_price'] = $v['goods_price'];

            $res[$k]['goods_price_format'] = $this->dscRepository->getPriceFormat($res[$k]['goods_price']);

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $res[$k]['free_rate'] = $v['free_rate'] ?? 0;
            }

            //ecmoban模板堂 --zhuo start 商品金额促销
            $row['goods_amount'] = $res[$k]['goods_price'] * $v['goods_number'];

            if (isset($goods['get_goods_consumption']) && $goods['get_goods_consumption']) {
                $res[$k]['amount'] = $this->dscRepository->getGoodsConsumptionPrice($goods['get_goods_consumption'], $row['goods_amount']);
            } else {
                $res[$k]['amount'] = $row['goods_amount'];
            }

            $res[$k]['subtotal'] = $row['goods_amount'];
            $res[$k]['formated_subtotal'] = $this->dscRepository->getPriceFormat($row['goods_amount'], false);
            $res[$k]['dis_amount'] = $row['goods_amount'] - $res[$k]['amount'];
            $res[$k]['dis_amount'] = number_format($res[$k]['dis_amount'], 2, '.', '');
            $res[$k]['discount_amount'] = $this->dscRepository->getPriceFormat($res[$k]['dis_amount'], false);
        }

        return $res;
    }

    /**
     * 获取活动购物车id
     * @return mixed
     */
    public function getCartRecId($user_id = 0, $act_id = 0, $ru_id = 0)
    {
        $sess = $this->sessionRepository->realCartMacIp();

        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;

        $res = Cart::select('rec_id')->where('parent_id', 0)
            ->where('act_id', $act_id)
            ->where('ru_id', $ru_id)
            ->where('rec_type', CART_GENERAL_GOODS);

        if ($user_id) {
            $res = $res->where('user_id', $user_id);
            Cart::where('session_id', $sess)->update(['user_id' => $user_id, 'session_id' => 0]);
        } else {
            $res = $res->where('session_id', $sess);
        }

        $res = $res->orderBy('parent_id', 'ASC')->orderBy('rec_id', 'DESC')
            ->get();
        $res = $res ? $res->toArray() : [];
        $rec_id = [];
        if ($res) {
            foreach ($res as $key => $val) {
                $rec_id[] = $val['rec_id'];
            }
            $rec_id = implode(',', $rec_id);
        }
        return $rec_id;
    }


    /**
     * 检查是否已在购物车
     * @return mixed
     */
    public function getCartInfo($user_id = 0, $rec_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $sess = $this->sessionRepository->realCartMacIp();
        $cart = Cart::where('user_id', $user_id)
            ->where('rec_id', $rec_id)
            ->where('rec_type', CART_GENERAL_GOODS);

        if ($user_id) {
            $cart = $cart->where('user_id', $user_id);
            Cart::where('session_id', $sess)->update(['user_id' => $user_id, 'session_id' => 0]);
        } else {
            $cart = $cart->where('session_id', $sess);
        }

        $cart = $cart->with([
            'getGoods' => function ($query) {
                $query->select(
                    'goods_id',
                    'goods_number',
                    'goods_thumb',
                    'model_price',
                    'integral',
                    'user_id',
                    'is_on_sale',
                    'cat_id',
                    'brand_id',
                    'group_number',
                    'model_attr',
                    'xiangou_num',
                    'is_minimum',
                    'minimum_start_date',
                    'minimum_end_date',
                    'minimum'
                );
            }
        ]);

        $cart = $cart->first();
        if ($cart === null) {
            return [];
        }
        $cart = $cart->toArray();
        $cart['buy_number'] = $cart['goods_number'] ?? 0;
        $cart = $cart['get_goods'] ? array_merge($cart, $cart['get_goods']) : $cart;
        // 当前商品正在最小起订量
        if ($cart['extension_code'] != 'package_buy') {
            if ($time >= $cart['minimum_start_date'] && $time <= $cart['minimum_end_date'] && $cart['is_minimum']) {
                $cart['is_minimum'] = 1;
            } else {
                $cart['is_minimum'] = 0;
            }
        }

        return $cart;
    }


    /**
     * 购物车选择促销活动
     *
     * @param int $user_id
     * @param int $goods_id
     * @param int $ru_id
     * @param int $act_id
     * @param bool $type
     * @return array
     */
    public function getFavourable($user_id = 0, $goods_id = 0, $ru_id = 0, $act_id = 0, $type = false)
    {
        $res = app(DiscountService::class)->activityListAll($user_id, $ru_id);

        $favourable = [];
        $fav_actid = [];
        if (empty($goods_id)) {
            foreach ($res as $rows) {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = $favourable[$rows['act_id']]['url'] = route('api.activity.show', ['act_id' => $rows['act_id']]);
                $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), $this->timeRepository->getLocalDate('Y-m-d', $rows['start_time']), $this->timeRepository->getLocalDate('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
                $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
            }
        } else {
            // 商品信息
            $goods = Goods::select('cat_id', 'brand_id')->where('goods_id', $goods_id)->first();
            $goods = $goods ? $goods->toArray() : [];

            $category_id = $goods['cat_id'] ?? 0;
            $brand_id = $goods['brand_id'] ?? 0;

            foreach ($res as $rows) {
                if ($rows['act_range'] == FAR_ALL) {
                    $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    if ($type) {
                        $fav_actid['act_id'] = $rows['act_id'];
                        break;
                    }
                } elseif ($rows['act_range'] == FAR_CATEGORY) {
                    /* 找出分类id的子分类id */
                    $id_list = [];
                    $raw_id_list = explode(',', $rows['act_range_ext']);

                    foreach ($raw_id_list as $id) {
                        /**
                         * 当前分类下的所有子分类
                         * 返回一维数组
                         */
                        $cat_list = app(DiscountService::class)->arr_foreach(app(DiscountService::class)->catList($id));
                        $id_list = array_merge($id_list, $cat_list);
                        array_unshift($id_list, $id);
                    }
                    $ids = join(',', array_unique($id_list));
                    if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                        if ($type) {
                            $fav_actid['act_id'] = $rows['act_id'];
                            break;
                        }
                    }
                } elseif ($rows['act_range'] == FAR_BRAND) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                        if ($type) {
                            $fav_actid['act_id'] = $rows['act_id'];
                            break;
                        }
                    }
                } elseif ($rows['act_range'] == FAR_GOODS) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                        if ($type) {
                            $fav_actid['act_id'] = $rows['act_id'];
                            break;
                        }
                    }
                }
            }
        }

        if ($type) {
            return $fav_actid;
        } else {
            if ($favourable) {
                foreach ($favourable as $key => $val) {
                    if ($key == $act_id) {
                        $favourable[$key]['is_checked'] = true;
                    } else {
                        $favourable[$key]['is_checked'] = false;
                    }
                }
                $favourable = collect($favourable)->values()->all();
            }

            return $favourable;
        }
    }

    /**
     * 更新购物车商品促销活动
     */
    public function update_cart_goods_fav($user_id, $rec_id, $act_id)
    {
        $sess = $this->sessionRepository->realCartMacIp();

        $res = Cart::where('rec_id', $rec_id)
            ->where('parent_id', 0)
            ->where('group_id', '');
        if ($user_id) {
            $res->where('user_id', $user_id)
                ->update(['act_id' => $act_id]);
        } else {
            $res->where('session_id', $sess)
                ->update(['act_id' => $act_id]);
        }
    }

    /**
     * 添加套餐组合商品（配件）到购物车（临时）
     * $goods_id 当前商品id
     * $number
     * $spec =  当前商品属性
     * $parent_attr  主件商品属性
     * $warehouse_id
     * $area_id
     * $area_city
     * $parent 主件商品id
     * $group_id
     * $add_group
     */
    public function addToCartCombo($uid = 0, $args)
    {
        // 主件商品属性
        if (!is_array($args['parent_attr'])) {
            if (!empty($args['parent_attr'])) {
                $parent_goods_attr = explode(',', $args['parent_attr']);
            } else {
                $parent_goods_attr = [];
            }
        }
        // 首次添加配件时，查看主件是否存在，否则添加主件
        $ok_arr = $this->getInsertGroupMain($args['parent'], $args['number'], $parent_goods_attr, 0, $args['group_id'], $args['warehouse_id'], $args['area_id'], $args['area_city'], $uid);

        if ($ok_arr) {
            if ($ok_arr['is_ok'] == 1) {
                $msg['error'] = 1;
                $msg['msg'] = lang('flow.goods_not_exists');
                return $msg;
            }
            if ($ok_arr['is_ok'] == 2) { // 商品已下架
                $msg['error'] = 1;
                $msg['msg'] = lang('flow.shelves_goods');
                return $msg;
            }
            if ($ok_arr['is_ok'] == 3 || $ok_arr['is_ok'] == 4) { //
                $msg['error'] = 1;
                $msg['msg'] = lang('flow.goods_null_number');
                return $msg;
            }
        }

        $_parent_id = $args['parent'];

        /* 取得配件商品信息 */
        $where = [
            'goods_id' => $args['goods_id'],
            'warehouse_id' => $args['warehouse_id'],
            'area_id' => $args['area_id'],
            'area_city' => $args['area_city'],
            'uid' => $uid
        ];
        $goods = $this->goodsMobileService->getGoodsInfo($where);

        if (empty($goods)) {   // 商品不存在
            $msg['error'] = 1;
            $msg['msg'] = lang('flow.fittings_goods_null');
            return $msg;
        }

        /* 是否正在销售 */
        if ($goods['is_on_sale'] == 0) {// 是否正在销售
            $msg['error'] = 1;
            $msg['msg'] = lang('flow.fittings_goods_null_sold');
            return $msg;
        }

        /* 不是配件时检查是否允许单独销售 */
        if (empty($args['parent']) && $goods['is_alone_sale'] == 0) {
            $msg['error'] = 1;
            $msg['msg'] = lang('flow.goods_oneself_sold');
            return $msg;
        }

        /* 商品仓库货品 */
        $prod = $this->goodsWarehouseService->getGoodsProductsProd($args['goods_id'], $args['warehouse_id'], $args['area_id'], $args['area_city'], $goods['model_attr']);

        if ($this->goodsAttrService->is_spec($args['spec']) && !empty($prod)) {
            $product_info = $this->goodsAttrService->getProductsInfo($args['goods_id'], $args['spec'], $args['warehouse_id'], $args['area_id'], $args['area_city']);
        }

        if (empty($product_info)) {
            $product_info = ['product_number' => 0, 'product_id' => 0];
        }

        /* 检查：库存 */
        if ($this->config['use_storage'] == 1) {
            $is_product = 0;
            //商品存在规格 是货品
            if ($this->goodsAttrService->is_spec($args['spec']) && !empty($prod)) {
                if (!empty($args['spec'])) {
                    /* 取规格的货品库存 */
                    if ($args['number'] > $product_info['product_number']) {
                        $msg['error'] = 1;
                        $msg['msg'] = lang('cart.stock_goods_null');
                        return $msg;
                    }
                }
            } else {
                $is_product = 1;
            }

            if ($is_product == 1) {
                //检查：商品购买数量是否大于总库存
                if ($args['number'] > $goods['goods_number']) {
                    $msg['error'] = 1;
                    $msg['msg'] = lang('cart.stock_goods_null');
                    return $msg;
                }
            }
        }

        /* 计算商品的促销价格 */
        $warehouse_area['warehouse_id'] = $args['warehouse_id'];
        $warehouse_area['area_id'] = $args['area_id'];
        $warehouse_area['area_city'] = $args['area_city'];

        // 属性价格
        $spec_price = $this->goodsAttrService->specPrice($args['spec'], $args['goods_id'], $warehouse_area);

        $goods['marketPrice'] += $spec_price;

        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($args['spec'], 'pice', $args['warehouse_id'], $args['area_id'], $args['area_city']);

        $goods_attr_id = isset($spec) ? join(',', $spec) : '';

        if ($uid) {
            $sess_id = $uid;
        } else {
            $sess_id = $this->sessionRepository->realCartMacIp();
        }

        /* 初始化要插入购物车的基本件数据 */
        $parent = [
            'user_id' => $uid,
            'session_id' => $sess_id,
            'goods_id' => $args['goods_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['marketPrice'],
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'model_attr' => $goods['model_attr'], //ecmoban模板堂 --zhuo 属性方式
            'warehouse_id' => $args['warehouse_id'], //ecmoban模板堂 --zhuo 仓库
            'area_id' => $args['area_id'], //ecmoban模板堂 --zhuo 仓库地区
            'area_city' => $args['area_city'],
            'ru_id' => $goods['user_id'], //ecmoban模板堂 --zhuo 商家ID
            'extension_code' => $goods['extension_code'],
            'is_gift' => 0,
            'commission_rate' => $goods['commission_rate'],
            'is_shipping' => $goods['is_shipping'],
            'rec_type' => CART_GENERAL_GOODS,
            'add_time' => $this->timeRepository->getGmTime(),
            'group_id' => $args['group_id']
        ];

        /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
        /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
        /* 受此优惠 */
        $basic_list = [];

        $res = GroupGoods::select('parent_id', 'goods_price')
            ->where('goods_id', $args['goods_id'])
            ->where('parent_id', $_parent_id)
            ->orderBy('goods_price')
            ->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $row) {
                $basic_list[$row['parent_id']] = $row['goods_price'];
            }
        }

        /* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
        foreach ($basic_list as $parent_id => $fitting_price) {
            $attr_info = $this->goodsAttrService->getGoodsAttrInfo($args['spec'], 'pice', $args['warehouse_id'], $args['area_id'], $args['area_city']);

            /* 检查该商品是否已经存在在购物车中 */
            $row = CartCombo::where('goods_id', $args['goods_id'])
                ->where('parent_id', $parent_id)
                ->where('extension_code', '<>', 'package_buy')
                ->where('rec_type', CART_GENERAL_GOODS)
                ->where('group_id', $args['group_id']);

            if (!empty($uid)) {
                $row = $row->where('user_id', $uid);
            } else {
                $row = $row->where('session_id', $sess_id);
            }

            $row = $row->count();

            if ($row) { //如果购物车已经有此物品，则更新
                $num = 1; //临时保存到数据库，无数量限制
                if ($this->goodsAttrService->is_spec($args['spec']) && !empty($prod)) {
                    $goods_storage = $product_info['product_number'];
                } else {
                    $goods_storage = $goods['goods_number'];
                }

                if ($this->config['use_storage'] == 0 || $num <= $goods_storage) {
                    $fittAttr_price = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0;

                    $CartComboOther = [
                        'goods_number' => $num,
                        'commission_rate' => $goods['commission_rate'],
                        'goods_price' => $fittAttr_price,
                        'product_id' => $product_info['product_id'],
                        'goods_attr' => $attr_info,
                        'goods_attr_id' => $goods_attr_id,
                        'market_price' => $goods['marketPrice'],
                        'warehouse_id' => $args['warehouse_id'],
                        'area_id' => $args['area_id'],
                        'area_city' => $args['area_city']
                    ];
                    $res = CartCombo::where('goods_id', $args['goods_id'])
                        ->where('parent_id', $parent_id)
                        ->where('extension_code', '<>', 'package_buy')
                        ->where('rec_type', CART_GENERAL_GOODS)
                        ->where('group_id', $args['group_id']);

                    if (!empty($uid)) {
                        $res = $res->where('user_id', $uid);
                    } else {
                        $res = $res->where('session_id', $sess_id);
                    }

                    $res->update($CartComboOther);
                } else {
                    $msg['error'] = 1;
                    $msg['msg'] = lang('cart.stock_goods_null');
                    return $msg;
                }
            } //购物车没有此物品，则插入
            else {
                /* 作为该基本件的配件插入 */
                $parent['goods_price'] = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
                $parent['goods_number'] = 1; //临时保存到数据库，无数量限制
                $parent['parent_id'] = $parent_id;

                /* 添加 */
                CartCombo::insert($parent);
            }
        }

        $msg['error'] = 0;
        $msg['msg'] = lang('flow.add_package_cart_success');
        return $msg;
    }


    //首次添加配件时，查看主件是否存在，否则添加主件
    public function getInsertGroupMain($goods_id, $num = 1, $goods_spec = [], $parent = 0, $group = '', $warehouse_id = 0, $area_id = 0, $area_city = 0, $user_id = 0)
    {
        $ok_arr['is_ok'] = 0;
        $spec = $goods_spec;

        /* 取得商品信息 */
        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'uid' => $user_id
        ];
        $goods = $this->goodsMobileService->getGoodsInfo($where);

        if (empty($goods)) {
            $ok_arr['is_ok'] = 1;
            return $ok_arr;
        }

        /* 是否正在销售 */
        if ($goods['is_on_sale'] == 0) {
            $ok_arr['is_ok'] = 2;
            return $ok_arr;
        }

        /* 商品仓库货品 */
        $prod = $this->goodsWarehouseService->getGoodsProductsProd($goods_id, $warehouse_id, $area_id, $area_city, $goods['model_attr']);

        if ($this->goodsAttrService->is_spec($spec) && !empty($prod)) {
            $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city);
        }

        if (empty($product_info)) {
            $product_info = ['product_number' => 0, 'product_id' => 0];
        }

        /* 检查：库存 */
        if ($this->config['use_storage'] == 1) {
            $is_product = 0;
            //商品存在规格 是货品
            if ($this->goodsAttrService->is_spec($spec) && !empty($prod)) {
                if (!empty($spec)) {
                    /* 取规格的货品库存 */
                    if ($num > $product_info['product_number']) {
                        $ok_arr['is_ok'] = 3;
                        return $ok_arr;
                    }
                }
            } else {
                $is_product = 1;
            }

            if ($is_product == 1) {
                //检查：商品购买数量是否大于总库存
                if ($num > $goods['goods_number']) {
                    $ok_arr['is_ok'] = 4;
                    return $ok_arr;
                }
            }
        }

        /* 计算商品的促销价格 */
        $warehouse_area['warehouse_id'] = $warehouse_id;
        $warehouse_area['area_id'] = $area_id;
        $warehouse_area['area_city'] = $area_city;

        // 属性价格
        $spec_price = $this->goodsAttrService->specPrice($spec, $goods_id, $warehouse_area);

        // 最终价格
        $goods_price = $this->goodsMobileService->getFinalPrice($user_id, $goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city);

        $goods['marketPrice'] += $spec_price;

        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city);
        $goods_attr_id = isset($spec) ? join(',', $spec) : '';

        if ($user_id) {
            $sess_id = $user_id;
        } else {
            $sess_id = $this->sessionRepository->realCartMacIp();
        }

        /* 初始化要插入购物车的基本件数据 */
        $parent = [
            'user_id' => $user_id,
            'session_id' => $sess_id,
            'goods_id' => $goods_id,
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['marketPrice'],
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'model_attr' => $goods['model_attr'], //ecmoban模板堂 --zhuo 属性方式
            'warehouse_id' => $warehouse_id, //ecmoban模板堂 --zhuo 仓库
            'area_id' => $area_id, //ecmoban模板堂 --zhuo 仓库地区
            'area_city' => $area_city,
            'ru_id' => $goods['user_id'], //ecmoban模板堂 --zhuo 商家ID
            'extension_code' => $goods['extension_code'],
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'rec_type' => CART_GENERAL_GOODS,
            'add_time' => $this->timeRepository->getGmTime(),
            'group_id' => $group
        ];

        /* 检查该套餐主件商品是否已经存在在购物车中 */
        $row = CartCombo::where('goods_id', $goods_id)
            ->where('parent_id', 0)
            ->where('extension_code', '<>', 'package_buy')
            ->where('rec_type', CART_GENERAL_GOODS)
            ->where('group_id', $group);

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $row = $row->where('session_id', $sess_id);
        }

        $row = $row->where('warehouse_id', $warehouse_id);

        $row = $row->count();

        if ($row) {
            $CartComboOther = [
                'goods_number' => $num,
                'goods_price' => $goods_price,
                'product_id' => $product_info['product_id'],
                'goods_attr' => addslashes($goods_attr),
                'goods_attr_id' => $goods_attr_id,
                'market_price' => $goods['marketPrice'],
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id
            ];
            $res = CartCombo::where('goods_id', $goods_id)
                ->where('parent_id', 0)
                ->where('extension_code', '<>', 'package_buy')
                ->where('rec_type', CART_GENERAL_GOODS)
                ->where('group_id', $group);

            if (!empty($user_id)) {
                $res = $res->where('user_id', $user_id);
            } else {
                $res = $res->where('session_id', $sess_id);
            }

            $res->update($CartComboOther);
        } else {
            $parent['goods_price'] = max($goods_price, 0);
            $parent['goods_number'] = $num;
            $parent['parent_id'] = 0;

            CartCombo::insert($parent);
        }
    }

    /**
     * 更新临时购物车（删除配件）
     * @param int $goods_id
     * @return string
     */
    public function deleteGroupGoods($user_id = 0, $goods_id = 0, $group_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = CartCombo::Where('goods_id', $goods_id)
            ->where('group_id', $group_id);

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->delete();
    }

    /**
     * 统计购物车配件数量
     * @param int $goods_id
     * @return string
     */
    public function countGroupGoods($user_id = 0, $parent_id = 0, $group_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = CartCombo::where('group_id', $group_id);

        if ($parent_id) {
            $cart = $cart->where('parent_id', $parent_id);
        }

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->count();
    }


    /**
     * 删除临时购物车主件配件购物车
     * @param int $goods_id
     * @return string
     */
    public function deleteParentGoods($user_id = 0, $parent_id = 0, $group_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = CartCombo::where('group_id', $group_id);

        if ($parent_id) {
            $cart = $cart->Where('goods_id', $parent_id)
                ->where('parent_id', 0);
        }

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->delete();
    }


    /**
     * 清空配件购物车配件
     * @param int $goods_id
     * @return string
     */
    public function deleteCartGroupGoods($user_id = 0, $group_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = Cart::where('group_id', $group_id);

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        return $cart->delete();
    }


    /**
     * 查询临时购物车中的组合套餐
     * @param int $user_id
     * @param int $group_id
     * @return array
     */
    public function selectGroupGoods($user_id = 0, $group_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = CartCombo::where('group_id', $group_id);

        if (!empty($user_id)) {
            $cart = $cart->where('user_id', $user_id);
        } else {
            $real_ip = $this->sessionRepository->realCartMacIp();
            $cart = $cart->where('session_id', $real_ip);
        }

        $cart = $cart->orderby('parent_id', 'asc')
            ->get();

        return $cart ? $cart->toArray() : [];
    }


    /**
     * 查询购物车中的组合套餐配件
     * @param int $goods_id
     * @return string
     */
    public function getCartGroupList($uid = 0, $rec_id = 0)
    {
        $user_id = isset($user_id) && !empty($user_id) ? intval($user_id) : 0;
        $cart = Cart::from('cart as a')
            ->select('b.goods_number', 'b.rec_id')
            ->leftjoin('cart as b', 'b.parent_id', '=', 'a.goods_id')
            ->where('a.rec_id', $rec_id)
            ->where('a.user_id', $uid)
            ->where('a.extension_code', '!=', 'package_buy')
            ->where('b.user_id', $uid)
            ->get();

        return $cart ? $cart->toArray() : [];
    }


    /**
     * 获取属性库存
     * @param int $goods_id
     * @return string
     */
    public function getProductNumber($goods_id = 0, $product_id = 0, $model_attr = 0)
    {
        /* 如果商品有规格则取规格商品信息 */
        if ($model_attr == 1) {
            $prod = ProductsWarehouse::where('goods_id', $goods_id)
                ->where('product_id', $product_id);
        } elseif ($model_attr == 2) {
            $prod = ProductsArea::where('goods_id', $goods_id)
                ->where('product_id', $product_id);
        } else {
            $prod = Products::where('goods_id', $goods_id)
                ->where('product_id', $product_id);
        }

        $prod = $prod->first();

        return $prod ? $prod->toArray() : [];
    }

    /*
    * 检查商品单品限购
    */
    public function xiangou_checked($goods_id, $num, $user_id, $is_cart = 0, $rec_type = CART_GENERAL_GOODS)
    {
        $nowTime = $this->timeRepository->getGmTime();
        $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($goods_id);
        $start_date = $xiangouInfo['xiangou_start_date'];
        $end_date = $xiangouInfo['xiangou_end_date'];
        $result = ['error' => 0];

        if ($xiangouInfo['is_xiangou'] == 1 && $nowTime >= $start_date && $nowTime < $end_date) {
            $cart_number = Cart::where('goods_id', $goods_id)->where('rec_type', $rec_type);

            if (!empty($user_id)) {
                $cart_number = $cart_number->where('user_id', $user_id);
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();
                $cart_number = $cart_number->where('session_id', $session_id);
            }

            $cart_number = $cart_number->value('goods_number');
            $cart_number = $cart_number ?? 0;

            $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods_id, $user_id);

            if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                $result['error'] = 2;
                $result['cart_number'] = $cart_number;
                return $result;//该商品购买已达到限购条件,无法再购买
            } else {
                if ($xiangouInfo['xiangou_num'] > 0) {
                    if ($is_cart == 0) {
                        if ($cart_number + $orderGoods['goods_number'] + $num > $xiangouInfo['xiangou_num']) {
                            $result['error'] = 1;
                            $result['cart_number'] = $xiangouInfo['xiangou_num'] - $cart_number - $orderGoods['goods_number'];
                            $result['can_buy_num'] = $xiangouInfo['xiangou_num'] - $orderGoods['goods_number'];
                            return $result;//对不起，该商品已经累计超过限购数量
                        }
                    } else {
                        if ($orderGoods['goods_number'] + $num > $xiangouInfo['xiangou_num']) {
                            $result['error'] = 1;
                            $result['cart_number'] = $cart_number;
                            return $result;//对不起，该商品已经累计超过限购数量
                        }
                    }
                }
            }
        }
        return $result;//未满足限购条件
    }

    /**
     * 清空购物车分期商品
     * @param
     */
    protected function clearQishuGoods($uid = 0)
    {
        $user_id = $uid > 0 ? intval($uid) : 0;

        if ($user_id > 0) {
            Cart::where('stages_qishu', '>', 0)->where('user_id', $user_id)->delete();
        }
    }

    /**
     * 购物车商品数量
     * @param
     */
    public function cartNum($uid = 0)
    {
        if ($uid) {
            $row['cart_number'] = Cart::where('user_id', $uid)
                ->where('rec_type', 0)
                ->where('store_id', 0)
                ->sum('goods_number');
        } else {
            $realip = request()->header('X-Client-Hash');
            if(empty($realip)){
                $row['cart_number'] = 0;
            }else{
                $session_id = $this->sessionRepository->realCartMacIp();
                $row['cart_number'] = Cart::where('session_id', $session_id)
                    ->where('rec_type', 0)
                    ->where('store_id', 0)
                    ->sum('goods_number');
            }
        }
        return $row;
    }

}
