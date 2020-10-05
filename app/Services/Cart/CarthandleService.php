<?php

namespace App\Services\Cart;


use App\Libraries\Error;
use App\Models\Cart;
use App\Models\Goods;
use App\Models\GroupGoods;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;

class CarthandleService
{
    protected $error;
    protected $sessionRepository;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $dscRepository;
    protected $config;
    protected $goodsCommonService;
    protected $timeRepository;
    protected $cartCommonService;
    protected $cartRepository;

    public function __construct(
        Error $error,
        SessionRepository $sessionRepository,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService,
        TimeRepository $timeRepository,
        CartCommonService $cartCommonService,
        CartRepository $cartRepository
    )
    {
        $this->error = $error;
        $this->sessionRepository = $sessionRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->goodsCommonService = $goodsCommonService;
        $this->timeRepository = $timeRepository;
        $this->cartCommonService = $cartCommonService;
        $this->cartRepository = $cartRepository;
    }

    /**
     * 添加商品到购物车
     *
     * @param $goods_id 商品编号
     * @param int $num 商品数量
     * @param array $spec 规格值对应的id数组
     * @param int $parent 基本件
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param string $stages_qishu
     * @param int $store_id
     * @param string $take_time
     * @param string $store_mobile
     * @param int $act_id
     * @return array
     * @throws \Exception
     */
    public function addtoCart($goods_id, $num = 1, $spec = [], $parent = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $stages_qishu = '-1', $store_id = 0, $take_time = '', $store_mobile = '', $act_id = 0)
    {
        $result = ['error' => 0, 'message' => '']; // 初始化
        $_parent_id = $parent;

        /* 取得商品信息 */
        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];
        $goods = Goods::where('goods_id', $goods_id);

        $goods = $goods->with([
            'getWarehouseGoods' => function ($query) use ($where) {
                $query->where('region_id', $where['warehouse_id']);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $goods = $this->baseRepository->getToArrayFirst($goods);

        if (empty($goods)) {
            return ['error' => ERR_NOT_EXISTS, 'message' => lang('flow.goods_not_exists')];
        }

        /* 库存 */
        $wg_number = $goods['get_warehouse_goods']['wg_number'] ?? 0;
        $wag_number = $goods['get_warehouse_area_goods']['wag_number'] ?? 0;
        if ($goods['model_price'] == 1) {
            $goods['goods_number'] = $wg_number;
        } elseif ($goods['model_price'] == 2) {
            $goods['goods_number'] = $wag_number;
        }

        /* 如果是门店一步购物，获取门店库存 */
        if ($store_id > 0) {
            $goods['goods_number'] = StoreGoods::where('goods_id', $goods_id)->where('store_id', $store_id)->value('goods_number');
        }

        $session_id = $this->sessionRepository->realCartMacIp();

        /* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
        if ($parent > 0) {
            $cart = Cart::where('goods_id', $parent)
                ->where('extension_code', '<>', 'package_buy');

            if (!empty(session('user_id'))) {
                $cart = $cart->where('user_id', session('user_id'));
            } else {
                $cart = $cart->where('session_id', $session_id);
            }

            $cart = $cart->count();

            if ($cart == 0) {
                return ['error' => ERR_NO_BASIC_GOODS, 'message' => lang('common.no_basic_goods')];
            }
        }

        /* 是否正在销售 */
        if ($goods['is_on_sale'] == 0) {
            return ['error' => ERR_NOT_ON_SALE, 'message' => lang('common.not_on_sale')];
        }

        /* 不是配件时检查是否允许单独销售 */
        if (empty($parent) && $goods['is_alone_sale'] == 0) {
            return ['error' => ERR_CANNT_ALONE_SALE, 'message' => lang('common.cannt_alone_sale')];
        }

        /* 如果商品有规格则取规格商品信息 配件除外 */
        if ($store_id > 0) {
            $prod = StoreProducts::where('goods_id', $goods_id)
                ->where('store_id', $store_id);
        } else {
            if ($goods['model_attr'] == 1) {
                $prod = ProductsWarehouse::where('goods_id', $goods_id)
                    ->where('warehouse_id', $warehouse_id);
            } elseif ($goods['model_attr'] == 2) {
                $prod = ProductsArea::where('goods_id', $goods_id)
                    ->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $prod = $prod->where('city_id', $area_city);
                }
            } else {
                $prod = Products::where('goods_id', $goods_id);
            }
        }

        $prod = $this->baseRepository->getToArrayFirst($prod);

        $is_spec = $this->goodsAttrService->sortGoodsAttrIdArray($spec);
        $is_spec = $is_spec ? true : false;

        if ($is_spec && !empty($prod)) {
            $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $spec, $warehouse_id, $area_id, $area_city, $store_id);
        }

        if (empty($product_info)) {
            $product_info = ['product_number' => 0, 'product_id' => 0];
        }

        /* 检查：库存 */
        if ($this->config['use_storage'] == 1) {
            if ($store_id > 0) {
                $lang_shortage = lang('common.store_shortage');
            } else {
                $lang_shortage = lang('flow.shortage');
            }
            $is_product = 0;
            //商品存在规格 是货品
            if (is_spec($spec) && !empty($prod)) {
                if (!empty($spec)) {
                    /* 取规格的货品库存 */
                    if ($num > $product_info['product_number']) {
                        return ['error' => ERR_OUT_OF_STOCK, 'message' => sprintf($lang_shortage, $product_info['product_number'])];
                    }
                }
            } else {
                $is_product = 1;
            }

            if ($is_product == 1) {
                //检查：商品购买数量是否大于总库存
                if ($num > $goods['goods_number']) {
                    return ['error' => ERR_OUT_OF_STOCK, 'message' => sprintf(lang('flow.shortage'))];
                }
            }
        }

        /* 计算商品的促销价格 */
        $warehouse_area['warehouse_id'] = $warehouse_id;
        $warehouse_area['area_id'] = $area_id;
        $warehouse_area['area_city'] = $area_city;

        if ($this->config['add_shop_price'] == 1) {
            $add_tocart = 1;
        } else {
            $add_tocart = 0;
        }

        $spec_price = $this->goodsAttrService->specPrice($spec, $goods_id, $warehouse_area);
        $goods_price = $this->goodsCommonService->getFinalPrice($goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart);
        $goods['market_price'] += $spec_price;
        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($spec, 'pice', $warehouse_id, $area_id, $area_city); //ecmoban模板堂 --zhuo
        $goods_attr_id = join(',', $spec);

        $sess = empty(session('user_id')) ? $session_id : '';

        /* 初始化要插入购物车的基本件数据 */
        $parent = [
            'user_id' => session('user_id', 0),
            'session_id' => $sess,
            'goods_id' => $goods_id,
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
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
            'rec_type' => session('one_step_buy') == '1' ? CART_ONESTEP_GOODS : CART_GENERAL_GOODS,
            'add_time' => $this->timeRepository->getGmTime(),
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee'],
            'commission_rate' => $goods['commission_rate'],
            'store_id' => $store_id,
            'store_mobile' => $store_mobile,
            'take_time' => $take_time,
            'act_id' => $act_id,
            'cost_price' => $goods['cost_price']
        ];

        //门店购物
        if ($store_id) {
            $parent['rec_type'] = CART_OFFLINE_GOODS;
        }

        /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
        /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
        /* 受此优惠 */
        $res = GroupGoods::where('goods_id', $goods_id)
            ->where('goods_price', $goods_price)
            ->where('parent_id', $_parent_id)
            ->orderBy('goods_price');

        $res = $this->baseRepository->getToArrayGet($res);

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

            if (!empty(session('user_id'))) {
                $res = $res->where('user_id', session('user_id'));
            } else {
                $res = $res->where('session_id', $session_id);
            }

            $res = $res->whereIn('goods_id', array_keys($basic_list));

            $res = $res->groupBy('goods_id');

            $res = $this->baseRepository->getToArrayGet($res);

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

            if (!empty(session('user_id'))) {
                $res = $res->where('user_id', session('user_id'));
            } else {
                $res = $res->where('session_id', $session_id);
            }

            $res = $res->whereIn('parent_id', array_keys($basic_count_list));

            $res = $res->groupBy('parent_id');

            $res = $this->baseRepository->getToArrayGet($res);

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

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */

            $row = Cart::select('goods_number', 'stages_qishu', 'rec_id')
                ->where('goods_id', $goods_id)
                ->where('parent_id', 0)
                ->where('goods_attr', $goods_attr)
                ->where('extension_code', '<>', 'package_buy')
                ->where('rec_type', $parent['rec_type'])
                ->where('group_id', '')
                ->where('is_gift', 0);

            if ($goods['model_attr'] > 0) {
                $row = $row->where('warehouse_id', $warehouse_id);
            }

            if ($store_id > 0) {
                $row = $row->where('store_id', $store_id);
            }

            if (!empty(session('user_id'))) {
                $row = $row->where('user_id', session('user_id'));
            } else {
                $row = $row->where('session_id', $session_id);
            }

            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) { //如果购物车已经有此物品，则更新
                if (!($row['stages_qishu'] != '-1' && $stages_qishu != '-1') && !($row['stages_qishu'] != '-1' && $stages_qishu == '-1') && !($row['stages_qishu'] == '-1' && $stages_qishu != '-1')) {
                    $num += $row['goods_number']; //这里是普通商品,数量进行累加;bylu
                }
                /*  @author-bylu  end */

                if (is_spec($spec) && !empty($prod)) {
                    $goods_storage = $product_info['product_number'];
                } else {
                    $goods_storage = $goods['goods_number'];
                }

                if ($this->config['use_storage'] == 0 || $num <= $goods_storage) {
                    $goods_price = $this->goodsCommonService->getFinalPrice($goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart); //ecmoban模板堂 --zhuo

                    $cartOther = [
                        'goods_number' => $num,
                        'stages_qishu' => $stages_qishu,
                        'goods_price' => $goods_price,
                        'commission_rate' => $goods['commission_rate'],
                        'area_id' => $area_id,
                        'area_city' => $area_city,
                        'act_id' => $act_id,
                        'freight' => $goods['freight'],
                        'tid' => $goods['tid'],
                        'cost_price' => $goods['cost_price']
                    ];
                    $res = Cart::where('goods_id', $goods_id)
                        ->where('parent_id', 0)
                        ->where('goods_attr', $goods_attr)
                        ->where('extension_code', '<>', 'package_buy')
                        ->where('rec_type', CART_GENERAL_GOODS)
                        ->where('group_id', 0);

                    if ($goods['model_attr'] > 0) {
                        $res = $res->where('warehouse_id', $warehouse_id);
                    }

                    if (!empty(session('user_id'))) {
                        $res = $res->where('user_id', session('user_id'));
                    } else {
                        $res = $res->where('session_id', $session_id);
                    }

                    $res->update($cartOther);
                } else {
                    return ['error' => ERR_OUT_OF_STOCK, 'message' => sprintf(lang('shopping_flow.shortage'), $num)];
                }

                $new_rec_id = $row['rec_id'];
            } else { //购物车没有此物品，则插入
                $goods_price = $this->goodsCommonService->getFinalPrice($goods_id, $num, true, $spec, $warehouse_id, $area_id, $area_city, 0, 0, $add_tocart); //ecmoban模板堂 --zhuo
                $parent['goods_price'] = max($goods_price, 0);
                $parent['goods_number'] = $num;
                $parent['parent_id'] = 0;

                //如果分期期数不为 -1,那么即为分期付款商品;bylu
                $parent['stages_qishu'] = $stages_qishu;

                $new_rec_id = Cart::insertGetId($parent);
            }

            $cart_value = $this->cartCommonService->getCartValue();

            //加入SESSION 用作购物车默认选中
            if ($cart_value) {

                //删除SESSION中无效的值
                $cart_value = $this->baseRepository->getExplode($cart_value);

                $rec_arr = Cart::whereIn('rec_id', $cart_value);
                $rec_arr = $this->baseRepository->getToArrayGet($rec_arr);
                $rec_arr = $this->baseRepository->getKeyPluck($rec_arr, 'rec_id');

                if (!$rec_arr) {
                    session()->forget('cart_value');
                }
            }

            if (session('one_step_buy') == 1) {
                $cart_value = $new_rec_id;
            } else {
                if ($new_rec_id > 0) {
                    if ($cart_value) {
                        if (!in_array($new_rec_id, $cart_value)) {
                            $cart_value = $this->baseRepository->getArrayPush($cart_value, $new_rec_id);
                        }
                    } else {
                        $cart_value = $new_rec_id;
                    }
                }
            }

            $this->cartRepository->pushCartValue($cart_value);
        }

        return $result;
    }
}
