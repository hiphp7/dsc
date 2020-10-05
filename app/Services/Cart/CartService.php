<?php

namespace App\Services\Cart;

use App\Models\AreaRegion;
use App\Models\Attribute;
use App\Models\Cart;
use App\Models\Goods;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\ShippingArea;
use App\Models\StoreGoods;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Erp\JigonManageService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderGoodsService;
use App\Services\Package\PackageGoodsService;

/**
 * 商城商品订单
 * Class CrowdFund
 * @package App\Services
 */
class CartService
{
    protected $baseRepository;
    protected $config;
    protected $timeRepository;
    protected $dscRepository;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $sessionRepository;
    protected $cartCommonService;
    protected $goodsCommonService;
    protected $orderGoodsService;
    protected $goodsWarehouseService;
    protected $jigonManageService;
    protected $packageGoodsService;
    protected $cartRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        SessionRepository $sessionRepository,
        CartCommonService $cartCommonService,
        GoodsCommonService $goodsCommonService,
        OrderGoodsService $orderGoodsService,
        GoodsWarehouseService $goodsWarehouseService,
        JigonManageService $jigonManageService,
        PackageGoodsService $packageGoodsService,
        CartRepository $cartRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->cartCommonService = $cartCommonService;
        $this->goodsCommonService = $goodsCommonService;
        $this->orderGoodsService = $orderGoodsService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->jigonManageService = $jigonManageService;
        $this->packageGoodsService = $packageGoodsService;
        $this->cartRepository = $cartRepository;
    }

    /**
     * 购物车商品信息
     *
     * @param array $where
     * @return mixed
     */
    public function getCartInfo($where = [])
    {
        $user_id = session('user_id', 0);
        $where['rec_type'] = isset($where['rec_type']) ? $where['rec_type'] : CART_GENERAL_GOODS;

        $row = Cart::selectRaw("*, COUNT(*) AS cart_number, SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount")
            ->where('rec_type', $where['rec_type']);

        /* 附加查询条件 start */
        if (isset($where['rec_id'])) {
            $where['rec_id'] = !is_array($where['rec_id']) ? explode(",", $where['rec_id']) : $where['rec_id'];

            if (is_array($where['rec_id'])) {
                $row = $row->whereIn('rec_id', $where['rec_id']);
            } else {
                $row = $row->where('rec_id', $where['rec_id']);
            }
        }

        if (isset($where['store_id'])) {
            $row = $row->where('store_id', $where['store_id']);
        } else {
            if ($where['rec_type'] != CART_OFFLINE_GOODS) {
                $row = $row->where('store_id', 0);
            }
        }

        if (isset($where['stages_qishu'])) {
            $row = $row->where('stages_qishu', $where['stages_qishu']);
        }
        /* 附加查询条件 end */

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = $row->where('session_id', $session_id);
        }

        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 购物车商品数量
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getCartCount($where = [])
    {
        if (empty($where)) {
            return 0;
        }

        $user_id = session('user_id', 0);
        $where['rec_type'] = isset($where['rec_type']) ? $where['rec_type'] : CART_GENERAL_GOODS;
        $store_id = $where['store_id'] ?? 0;

        $row = Cart::whereRaw(1);

        /* 附加查询条件 start */
        if (isset($where['rec_type'])) {
            $row = $row->where('rec_type', $where['rec_type']);
        }

        if (isset($where['rec_id'])) {
            $where['rec_id'] = !is_array($where['rec_id']) ? explode(",", $where['rec_id']) : $where['rec_id'];
            $row = $row->whereIn('rec_id', $where['rec_id']);
        }

        if (isset($where['stages_qishu'])) {
            $row = $row->where('stages_qishu', $where['stages_qishu']);
        }

        if ($store_id > 0) {
            $row = $row->where('store_id', $where['store_id']);
        } else {
            if ($where['rec_type'] != CART_OFFLINE_GOODS) {
                $row = $row->where('store_id', 0);
            }
        }

        if (isset($where['is_gift'])) {
            $row = $row->where('is_gift', $where['is_gift']);
        }

        if (isset($where['parent_id'])) {
            $row = $row->where('parent_id', $where['parent_id']);
        }
        /* 附加查询条件 end */

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = $row->where('session_id', $session_id);
        }

        $count = $row->count();

        return $count;
    }

    /**
     * 购物车门店地址信息
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getCartOfflineStoreGoods($where = [])
    {
        if (isset($where['rec_id']) && $where['rec_id']) {
            $where['rec_id'] = !is_array($where['rec_id']) ? explode(",", $where['rec_id']) : $where['rec_id'];
        } else {
            return [];
        }

        $res = Cart::whereIn('rec_id', $where['rec_id']);

        if (isset($where['store_id'])) {
            $res = $res->where('store_id', $where['store_id']);
        }

        $res = $res->whereHas('getOfflineStore')
            ->whereHas('getStoreGoods');

        $res = $res->with([
            'getOfflineStoreArea' => function ($query) {
                $query->select('id', 'stores_name', 'stores_address');
            }
        ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            $res = $this->baseRepository->getArrayMerge($res, $res['get_offline_store_area']);

            $res['province'] = $res['get_offline_store_area']['get_region_province']['region_name'];
            $res['city'] = $res['get_offline_store_area']['get_region_city']['region_name'];
            $res['district'] = $res['get_offline_store_area']['get_region_district']['region_name'];
        }

        return $res;
    }

    /**
     * 购物车商家ID
     * $ru_id
     */
    public function getCartSellerlist($cart_value)
    {
        $cart_value = $this->baseRepository->getExplode($cart_value);

        $ru_id = '';
        if ($cart_value) {
            $ru_id = Cart::selectRaw("GROUP_CONCAT(ru_id) AS ru_id")
                ->whereIn('rec_id', $cart_value)
                ->value('ru_id');

            $ru_id = $ru_id ? $ru_id : '';
        }

        return $ru_id;
    }

    /**
     * 区域获得自提点
     */
    public function getSelfPointCart($where = [])
    {
        $point_id = isset($where['point_id']) && !empty($where['point_id']) ? intval($where['point_id']) : 0;
        $shipping_dateStr = session('flow_consignee.shipping_dateStr', '');

        if (isset($where['parent_id'])) {
            $region_id = Region::select('region_id')
                ->where('parent_id', $where['parent_id'])
                ->get();

            $region_id = $region_id ? $region_id->toArray() : [];
            $where['parent_id'] = $region_id ? collect($region_id)->flatten()->all() : [];
        }

        $self_point = AreaRegion::select('region_id', 'shipping_area_id')
            ->whereHas('getRegion')
            ->whereHas('getShippingPoint', function ($query) use ($point_id) {
                $query = $query->where('name', '<>', '');

                if ($point_id > 0) {
                    $query->where('id', $point_id);
                }
            });

        if (isset($where['parent_id']) && $where['parent_id']) {
            $self_point = $self_point->whereIn('region_id', $where['parent_id']);
        }

        if (isset($where['district'])) {
            $where['district'] = $this->baseRepository->getExplode($where['district']);

            $self_point = $self_point->whereIn('region_id', $where['district']);
        }

        $self_point = $self_point->with([
            'getRegion' => function ($query) {
                $query->select('region_id', 'region_name', 'parent_id as city', 'parent_id as city', 'parent_id as city');
            },
            'getShippingPoint' => function ($query) use ($point_id) {
                $query = $query->select('shipping_area_id', 'name', 'user_name', 'id', 'id as point_id', 'address', 'mobile', 'img_url', 'anchor', 'line');

                if ($point_id > 0) {
                    $query->where('id', $point_id);
                }
            }
        ]);

        if (isset($where['limit']) && $where['limit'] > 0) {
            $self_point = $self_point->take($where['limit']);
        }

        $self_point = $self_point->get();

        $self_point = $self_point ? $self_point->toArray() : [];

        if ($self_point) {
            foreach ($self_point as $key => $row) {
                $row = $row['get_region'] ? array_merge($row, $row['get_region']) : $row;
                $row = $row['get_shipping_point'] ? array_merge($row, $row['get_shipping_point']) : $row;

                if ($row['shipping_area_id']) {
                    $shipping_area = ShippingArea::where('shipping_area_id', $row['shipping_area_id'])
                        ->whereHas('getShipping');

                    $shipping_area = $shipping_area->with([
                        'getShipping' => function ($query) {
                            $query->select('shipping_id', 'shipping_name', 'shipping_code');
                        }
                    ]);

                    $shipping_area = $this->baseRepository->getToArrayFirst($shipping_area);
                    $shipping_area = $shipping_area && isset($shipping_area['get_shipping']) ? $this->baseRepository->getArrayMerge($shipping_area, $shipping_area['get_shipping']) : $shipping_area;

                    $row['shipping_id'] = $shipping_area && isset($shipping_area['shipping_id']) ? $shipping_area['shipping_id'] : 0;
                    $row['shipping_name'] = $shipping_area && isset($shipping_area['shipping_name']) ? $shipping_area['shipping_name'] : 0;
                    $row['shipping_code'] = $shipping_area && isset($shipping_area['shipping_code']) ? $shipping_area['shipping_code'] : '';
                }

                $self_point[$key] = $row;

                $self_point[$key]['img_url'] = isset($row['img_url']) && !empty($row['img_url']) ? $this->dscRepository->getImagePath($row['img_url']) : '';

                if ($point_id > 0 && $row['point_id'] == $point_id) {
                    $self_point[$key]['is_check'] = 1;
                }

                if ($shipping_dateStr) {
                    $self_point[$key]['shipping_dateStr'] = $shipping_dateStr;
                } else {
                    $self_point[$key]['shipping_dateStr'] = $this->timeRepository->getLocalDate("m", $this->timeRepository->getLocalStrtoTime(' +1day')) . "月" . $this->timeRepository->getLocalDate("d", $this->timeRepository->getLocalStrtoTime(' +1day')) . "日&nbsp;【周" . $this->timeRepository->transitionDate($this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime(' +1day'))) . "】";
                }
            }
        }

        return $self_point;
    }

    /**
     * 检查该项是否为基本件 以及是否存在配件
     * 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_idgoods_number为1
     *
     * @param array $where
     * @return  array
     */
    public function getOffersAccessoriesList($where = [])
    {
        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);

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
     * 调用购物车信息
     *
     * @access  public
     * @return  string
     */
    public function getUserCartInfo($type = 0)
    {
        $user_id = session('user_id', 0);
        $session_id = $this->sessionRepository->realCartMacIp();

        $row = Cart::where('rec_type', CART_GENERAL_GOODS)
            ->whereHas('getGoods');

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_thumb');
            }
        ]);

        if ($type == 1) {
            $row = $row->take(4);
        }

        $row = $this->baseRepository->getToArrayGet($row);

        $arr = [];
        $cart_value = '';
        if ($row) {
            foreach ($row as $k => $v) {
                $v = $v['get_goods'] ? array_merge($v, $v['get_goods']) : $v;

                $arr[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                $arr[$k]['short_name'] = $this->config['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($v['goods_name'], $this->config['goods_name_length']) : $v['goods_name'];
                $arr[$k]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $v['goods_id']], $v['goods_name']);
                $arr[$k]['goods_number'] = $v['goods_number'];
                $arr[$k]['goods_name'] = $v['goods_name'];
                $arr[$k]['goods_price'] = $this->dscRepository->getPriceFormat($v['goods_price']);
                $arr[$k]['rec_id'] = $v['rec_id'];
                $arr[$k]['warehouse_id'] = $v['warehouse_id'];
                $arr[$k]['area_id'] = $v['area_id'];
                $cart_value = !empty($cart_value) ? $cart_value . ',' . $v['rec_id'] : $v['rec_id'];

                $properties = $this->goodsAttrService->getGoodsProperties($v['goods_id'], $v['warehouse_id'], $v['area_id'], $v['area_city'], $v['goods_attr_id'], 1);

                if ($properties['spe']) {
                    $arr[$k]['spe'] = array_values($properties['spe']);
                } else {
                    $arr[$k]['spe'] = [];
                }
            }
        }

        $row = Cart::selectRaw("SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount")->where('rec_type', CART_GENERAL_GOODS);

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $row = $row->where('session_id', $session_id);
        }

        $row = $this->baseRepository->getToArrayFirst($row);

        if ($row) {
            $number = intval($row['number']);
            $amount = floatval($row['amount']);
        } else {
            $number = 0;
            $amount = 0;
        }

        if ($type == 1) {
            $cart = ['goods_list' => $arr, 'number' => $number, 'amount' => $this->dscRepository->getPriceFormat($amount, false)];
            return $cart;
        } elseif ($type == 2) {
            $cart = ['goods_list' => $arr, 'number' => $number, 'amount' => $this->dscRepository->getPriceFormat($amount, false)];
            return $cart;
        } else {
            $GLOBALS['smarty']->assign('number', $number);
            $GLOBALS['smarty']->assign('amount', $amount);
            $GLOBALS['smarty']->assign('cart_info', $row);

            $GLOBALS['smarty']->assign('cart_value', $cart_value); //by wang
            $GLOBALS['smarty']->assign('str', sprintf(lang('common.cart_info'), $number, $this->dscRepository->getPriceFormat($amount, false)));
            $GLOBALS['smarty']->assign('goods', $arr);

            $output = $GLOBALS['smarty']->fetch('library/cart_info.lbi');
            return $output;
        }
    }

    /***
     * 购物车商品属性
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function cartGoodsAttr($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $res = Attribute::where('attr_type', '<>', 0);

        $res = $res->whereHas('getGoodsAttr', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id)
                ->whereHas('getGoods');
        });

        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];
        $res = $res->with([
            'getGoodsAttrList' => function ($query) use ($where) {
                $query = $query->where('goods_id', $where['goods_id']);

                $query = $query->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'model_attr');
                    },
                    'getGoodsAttribute' => function ($query) {
                        $query->select('attr_id', 'attr_name', 'attr_type', 'sort_order');
                    },
                    'getGoodsWarehouseAttr' => function ($query) use ($where) {
                        $query->select('goods_id', 'attr_price')
                            ->where('warehouse_id', $where['warehouse_id']);
                    },
                    'getGoodsWarehouseAreaAttr' => function ($query) use ($where) {
                        $query = $query->select('goods_id', 'attr_price')
                            ->where('area_id', $where['area_id']);

                        if ($where['area_pricetype'] == 1) {
                            $query->where('city_id', $where['area_city']);
                        }
                    }
                ]);

                $query->orderBy('goods_attr_id');
            }
        ]);

        $res = $res->orderByRaw('sort_order, attr_id asc');

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $list[$row['attr_id']]['attr_type'] = $row['attr_type'];
                $list[$row['attr_id']]['name'] = $row['attr_name'];
                $list[$row['attr_id']]['attr_id'] = $row['attr_id'];

                if ($row['get_goods_attr_list']) {
                    foreach ($row['get_goods_attr_list'] as $idx => $val) {
                        $attr_price = $val['attr_price'];
                        if (isset($val['get_goods']) && $val['get_goods']) {
                            if ($val['get_goods']['model_attr'] == 1) {
                                $attr_price = $val['get_goods_warehouse_attr']['attr_price'];
                            } elseif ($val['get_goods']['model_attr'] == 2) {
                                $attr_price = $val['get_goods_warehouse_area_attr']['attr_price'];
                            }
                        }

                        $attr = [
                            'label' => $val['attr_value'],
                            'price' => $attr_price,
                            'format_price' => $this->dscRepository->getPriceFormat($attr_price, false),
                            'id' => $val['goods_attr_id']
                        ];

                        $list[$row['attr_id']]['values'][$idx] = $attr;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 更新购物车商品选中状态
     *
     * @param $rec_id
     */
    public function cartUpdateGoodsChecked($user_id, $rec_id)
    {
        $rec_id = $this->baseRepository->getExplode($rec_id);

        if ($rec_id) {
            $this->updateCartNoChecked($user_id, $rec_id);
            $this->updateCartChecked($user_id, $rec_id);

            /* 存到session */
            $this->cartRepository->pushCartValue($rec_id);
        } else {
            session(['cart_value' => 0]);
        }
    }

    /**
     * 更新为选中状态（0|is_checked）
     *
     * @param $row
     * @param $rec_id
     */
    private function updateCartNoChecked($user_id, $rec_id)
    {
        if ($user_id > 0) {
            $row = Cart::where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = Cart::where('session_id', $session_id);
        }

        $row->whereNotIn('rec_id', $rec_id)
            ->update([
                'is_checked' => 0
            ]);
    }

    /**
     * 更新选中状态 （1|is_checked）
     *
     * @param $row
     * @param $rec_id
     */
    private function updateCartChecked($user_id, $rec_id)
    {
        if ($user_id > 0) {
            $row = Cart::where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = Cart::where('session_id', $session_id);
        }

        $row->whereIn('rec_id', $rec_id)
            ->update([
                'is_checked' => 1
            ]);
    }

    /**
     * 过滤商品数组获取购物车商品rec_id
     *
     * @param array $list
     * @return int
     */
    public function getSatrtCartVlaue($list = [])
    {
        $cart_value = 0;
        if ($list) {
            $cart_value = $this->baseRepository->getFlatten($list);
            $cart_value = $this->baseRepository->getImplode($cart_value);
        }

        $this->cartRepository->pushCartValue($cart_value);

        return $cart_value;
    }

    /**
     * 更新购物车中的商品数量
     *
     * @param array $arr
     * @throws \Exception
     */
    public function getFlowUpdateCart($arr = [])
    {
        $user_id = session('user_id', 0);
        $session_id = $this->sessionRepository->realCartMacIp();

        /* 处理 */
        if ($arr) {
            foreach ($arr as $key => $val) {
                $val = intval(make_semiangle($val));
                if ($val <= 0 || !is_numeric($key)) {
                    continue;
                }

                //查询：
                $goods = Cart::where('rec_id', $key);

                if (!empty($user_id)) {
                    $goods = $goods->where('user_id', $user_id);
                } else {
                    $goods = $goods->where('session_id', $session_id);
                }

                $goods = $goods->first();

                $goods = $goods ? $goods->toArray() : [];

                $where = [
                    'warehouse_id' => $goods['warehouse_id'],
                    'area_id' => $goods['area_id'],
                    'area_city' => $goods['area_city'],
                    'area_pricetype' => $this->config['area_pricetype']
                ];

                $row = Goods::where('goods_id', $goods['goods_id']);

                $row = $row->with([
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

                $row = $row->first();

                $row = $row ? $row->toArray() : [];

                if (empty($row)) {
                    continue;
                }

                /* 库存 */
                $wg_number = $row['get_warehouse_goods']['wg_number'] ?? 0;
                $wag_number = $row['get_warehouse_area_goods']['wag_number'] ?? 0;
                if ($row['model_price'] == 1) {
                    $row['goods_number'] = $wg_number;
                } elseif ($row['model_price'] == 2) {
                    $row['goods_number'] = $wag_number;
                }

                //ecmoban模板堂 --zhuo start 限购
                $nowTime = $this->timeRepository->getGmTime();
                $xiangouInfo = $this->goodsCommonService->getPurchasingGoodsInfo($goods['goods_id']);
                $start_date = $xiangouInfo['xiangou_start_date'];
                $end_date = $xiangouInfo['xiangou_end_date'];

                if ($xiangouInfo['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
                    $user_id = session('user_id', 0);
                    $orderGoods = $this->orderGoodsService->getForPurchasingGoods($start_date, $end_date, $goods['goods_id'], $user_id);

                    if ($orderGoods['goods_number'] >= $xiangouInfo['xiangou_num']) {
                        $result['message'] = sprintf(lang('flow.purchase_Prompt'), $row['goods_name']);

                        //更新购物车中的商品数量
                        Cart::where('rec_id', $key)->update(['goods_number' => 0]);

                        return show_message($result['message'], lang('shopping_flow.back_to_cart'), 'flow.php');
                    } else {
                        if ($xiangouInfo['xiangou_num'] > 0) {
                            if ($xiangouInfo['is_xiangou'] == 1 && $orderGoods['goods_number'] + $val > $xiangouInfo['xiangou_num']) {
                                $result['message'] = sprintf(lang('flow.purchasing_prompt'), $row['goods_name']);

                                //更新购物车中的商品数量
                                $cart_Num = $xiangouInfo['xiangou_num'] - $orderGoods['goods_number'];
                                Cart::where('rec_id', $key)->update(['goods_number' => $cart_Num]);

                                return show_message($result['message'], lang('shopping_flow.back_to_cart'), 'flow.php');
                            }
                        }
                    }
                }
                //ecmoban模板堂 --zhuo end 限购

                //查询：系统启用了库存，检查输入的商品数量是否有效
                if (intval($this->config['use_storage']) > 0 && $goods['extension_code'] != 'package_buy') {
                    if ($row['goods_number'] < $val) {
                        return show_message(sprintf(
                            lang('cart.stock_insufficiency'),
                            $row['goods_name'],
                            $row['goods_number'],
                            $row['goods_number']
                        ));
                    }

                    /* 是货品 */
                    if (!empty($goods['product_id'])) {
                        if ($goods['model_attr'] == 1) {
                            $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('product_id', $goods['product_id']);
                        } elseif ($goods['model_attr'] == 2) {
                            $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('product_id', $goods['product_id']);
                        } else {
                            $prod = Products::where('goods_id', $goods['goods_id'])->where('product_id', $goods['product_id']);
                        }

                        $product_number = $prod->value('product_number');

                        if ($product_number < $val) {
                            return show_message(sprintf(lang('cart.stock_insufficiency'), $row['goods_name'], $product_number, $product_number));
                        }
                    }
                } elseif (intval($this->config['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
                    if ($this->packageGoodsService->judgePackageStock($goods['goods_id'], $val)) {
                        return show_message(lang('shopping_flow.package_stock_insufficiency'));
                    }
                }

                /* 查询：检查该项是否为基本件 以及是否存在配件 */
                /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
                $where = [
                    'rec_id' => $key,
                    'extension_code' => ['<>', 'package_buy'],
                ];
                $offers_accessories_res = $this->getOffersAccessoriesList($where);

                //订货数量大于0
                if ($val > 0) {
                    /* 判断是否为超出数量的优惠价格的配件 删除 */
                    $row_num = 1;
                    foreach ($offers_accessories_res as $offers_accessories_row) {
                        if ($row_num > $val) {
                            $del = Cart::where('rec_id', $offers_accessories_row['rec_id'])
                                ->where('group_id', '');

                            if (!empty($user_id)) {
                                $del = $del->where('user_id', $user_id);
                            } else {
                                $del = $del->where('session_id', $session_id);
                            }

                            $del->delete();
                        }

                        $row_num++;
                    }

                    /* 处理超值礼包 */
                    if ($goods['extension_code'] == 'package_buy') {
                        //更新购物车中的商品数量
                        $update = Cart::where('rec_id', $key)
                            ->where('group_id', '');

                        if (!empty($user_id)) {
                            $update = $update->where('user_id', $user_id);
                        } else {
                            $update = $update->where('session_id', $session_id);
                        }

                        $update->update(['goods_number' => $val]);
                    } /* 处理普通商品或非优惠的配件 */
                    else {
                        $attr_id = empty($goods['goods_attr_id']) ? [] : explode(',', $goods['goods_attr_id']);
                        $goods_price = $this->goodsCommonService->getFinalPrice($goods['goods_id'], $val, true, $attr_id, $goods['warehouse_id'], $goods['area_id'], $goods['area_city']);

                        //更新购物车中的商品数量
                        $update = Cart::where('rec_id', $key)
                            ->where('group_id', '');

                        if (!empty($user_id)) {
                            $update = $update->where('user_id', $user_id);
                        } else {
                            $update = $update->where('session_id', $session_id);
                        }

                        $update->update(['goods_number' => $val, 'goods_price' => $goods_price]);
                    }
                } //订货数量等于0
                else {
                    /* 如果是基本件并且有优惠价格的配件则删除优惠价格的配件 */
                    foreach ($offers_accessories_res as $offers_accessories_row) {
                        $del = Cart::where('rec_id', $offers_accessories_row['rec_id'])
                            ->where('group_id', '');

                        if (!empty($user_id)) {
                            $del = $del->where('user_id', $user_id);
                        } else {
                            $del = $del->where('session_id', $session_id);
                        }

                        $del->delete();
                    }

                    $del = Cart::where('rec_id', $key)
                        ->where('group_id', '');

                    if (!empty($user_id)) {
                        $del = $del->where('user_id', $user_id);
                    } else {
                        $del = $del->where('session_id', $session_id);
                    }

                    $del->delete();
                }
            }
        }


        /* 删除所有赠品 */
        $del = Cart::where('is_gift', '<>', 0);

        if (!empty($user_id)) {
            $del = $del->where('user_id', $user_id);
        } else {
            $del = $del->where('session_id', $session_id);
        }

        $del->delete();
    }

    /**
     * 检查订单中商品库存
     *
     * @param array $arr
     * @param int $store_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @throws \Exception
     */
    public function getFlowCartStock($arr = [], $store_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        if ($arr) {
            foreach ($arr as $rec_id => $val) {
                $val = intval(make_semiangle($val));
                if ($val <= 0 || !is_numeric($rec_id)) {
                    continue;
                }

                $where = [
                    'rec_id' => $rec_id
                ];
                $goods = $this->getCartInfo($where);

                if (empty($goods)) {
                    continue;
                }
                $goods_info = [];
                $goods_info['goods_id'] = $goods['goods_id'];
                if ($store_id > 0) {
                    $goods_info['goods_number'] = StoreGoods::where('goods_id', $goods['goods_id'])
                        ->where('store_id', $store_id)
                        ->value('goods_number');
                } else {
                    $where = [
                        'goods_id' => $goods['goods_id'],
                        'warehouse_id' => $warehouse_id,
                        'area_id' => $area_id,
                        'area_city' => $area_city,
                        'area_pricetype' => $this->config['area_pricetype']
                    ];

                    $goods_info = Goods::where('goods_id', $goods['goods_id']);

                    $goods_info = $goods_info->with([
                        'getWarehouseGoods' => function ($query) use ($where) {
                            $where['warehouse_id'] = $where['warehouse_id'] ?? 0;
                            $query->where('region_id', $where['warehouse_id']);
                        },
                        'getWarehouseAreaGoods' => function ($query) use ($where) {
                            $query = $query->where('region_id', $where['area_id']);

                            if ($where['area_pricetype'] == 1) {
                                $query->where('city_id', $where['area_city']);
                            }
                        }
                    ]);

                    $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

                    if ($goods_info) {
                        /* 库存 */
                        $wg_number = $goods_info['get_warehouse_goods']['wg_number'] ?? 0;
                        $wag_number = $goods_info['get_warehouse_area_goods']['wag_number'] ?? 0;
                        if ($goods_info['model_price'] == 1) {
                            $goods_info['goods_number'] = $wg_number;
                        } elseif ($goods_info['model_price'] == 2) {
                            $goods_info['goods_number'] = $wag_number;
                        }
                    }
                }

                $goods_info['goods_number'] = $goods_info['goods_number'] ?? 0;

                //系统启用了库存，检查输入的商品数量是否有效
                if (intval($this->config['use_storage']) > 0 && $goods['extension_code'] != 'package_buy' && $store_id == 0) {
                    /* 是货品 */
                    $goods['product_id'] = trim($goods['product_id']);
                    if (!empty($goods['product_id'])) {
                        if ($goods['model_attr'] == 1) {
                            $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('product_id', $goods['product_id']);
                        } elseif ($goods['model_attr'] == 2) {
                            $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('product_id', $goods['product_id']);

                            if ($this->config['area_pricetype'] == 1) {
                                $prod = $prod->where('city_id', $area_city);
                            }
                        } else {
                            $prod = Products::where('goods_id', $goods['goods_id'])->where('product_id', $goods['product_id']);
                        }

                        $product_number = $prod->value('product_number');

                        if ($product_number < $val) {
                            return show_message(sprintf(
                                lang('shopping_flow.stock_insufficiency'),
                                $goods_info['goods_name'] ?? '',
                                $product_number,
                                $product_number
                            ));
                        }
                    } else {
                        if ($goods_info['goods_number'] < $val) {
                            return show_message(sprintf(
                                lang('shopping_flow.stock_insufficiency'),
                                $goods_info['goods_name'] ?? '',
                                $goods_info['goods_number'],
                                $goods_info['goods_number']
                            ));
                        }
                    }
                } elseif (intval($this->config['use_storage']) > 0 && $store_id > 0) {
                    $goodsInfo = StoreGoods::where('store_id', $store_id)->where('goods_id', $goods_info['goods_id'])->first();
                    $goodsInfo = $goodsInfo ? $goodsInfo->toArray() : [];

                    $products = $this->goodsWarehouseService->getWarehouseAttrNumber($goods_info['goods_id'], $goods['goods_attr_id'], $warehouse_id, $area_id, $area_city, '', $store_id); //获取属性库存
                    $attr_number = $products ? $products['product_number'] : 0;
                    if ($goods['goods_attr_id']) { //当商品没有属性库存时
                        $goods_info['goods_number'] = $attr_number;
                    } else {
                        $goods_info['goods_number'] = $goodsInfo['goods_number'] ?? 0;
                    }
                    if ($goods_info['goods_number'] < $val) {
                        return show_message(sprintf(
                            lang('shopping_flow.stock_store_shortage'),
                            $goods_info['goods_name'] ?? '',
                            $goods_info['goods_number'],
                            $goods_info['goods_number']
                        ));
                    }
                } elseif (intval($this->config['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
                    if ($this->packageGoodsService->judgePackageStock($goods['goods_id'], $val)) {
                        return show_message(lang('shopping_flow.package_stock_insufficiency'));
                    }
                } elseif (isset($goods_info['cloud_id']) && $goods_info['cloud_id'] > 0) {
                    $cloud_number = $this->jigonManageService->jigonGoodsNumber(['product_id' => $goods['product_id']]);

                    if ($cloud_number < $val) {
                        return show_message(sprintf(lang('cart.stock_insufficiency'), $goods_info['goods_name'] ?? '', $cloud_number, $cloud_number));
                    }
                }
            }
        }
    }

    /**
     * 获取购物车活动商品父级商品
     *
     * @param int $user_id
     * @return mixed
     */
    public function getCartParentList($user_id = 0)
    {

        $res = Cart::select('goods_id')->where('parent_id', 0)
            ->where('is_gift', 0)
            ->where('goods_number', '>', 0)
            ->where('extension_code', '<>', 'package_buy');

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }
}
