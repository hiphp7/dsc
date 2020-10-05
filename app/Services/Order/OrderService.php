<?php

namespace App\Services\Order;

use App\Models\Cart;
use App\Models\DeliveryGoods;
use App\Models\DeliveryOrder;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\Payment;
use App\Models\SolveDealconcurrent;
use App\Models\StoreOrder;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsWarehouseService;

/**
 * 商城商品订单
 * Class CrowdFund
 * @package App\Services
 */
class OrderService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $config;
    protected $commentService;
    protected $goodsWarehouseService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        CommentService $commentService,
        GoodsWarehouseService $goodsWarehouseService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->commentService = $commentService;
        $this->goodsWarehouseService = $goodsWarehouseService;
    }

    /**
     * 获取订单详情
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getOrderInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        if (isset($where['main_order_id'])) {
            $res = OrderInfo::selectRaw("GROUP_CONCAT(order_id) AS order_id, GROUP_CONCAT(order_sn) AS order_sn")
                ->where('main_order_id', $where['main_order_id']);
        } else {
            $res = OrderInfo::whereRaw(1);
        }

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        if (isset($where['order_sn'])) {
            $res = $res->where('order_sn', $where['order_sn']);
        }

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        $res = $res->with([
            'getPayment',
            'getSellerNegativeOrder'
        ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     * 获取未支付,有效订单信息
     *
     * @param int $order_id
     * @param int $main_order_id
     * @return  array
     */
    public function getUnPayedOrderInfo($order_id = 0, $main_order_id = 0)
    {
        if ($main_order_id > 0) {
            $model = OrderInfo::selectRaw("GROUP_CONCAT(order_id) AS order_id, GROUP_CONCAT(order_sn) AS order_sn")
                ->where('main_order_id', $main_order_id);
        } else {
            $model = OrderInfo::query()->where('order_id', $order_id);
        }

        // 订单状态 未确认，已确认、已分单
        $order_status = [
            OS_UNCONFIRMED,
            OS_CONFIRMED,
            OS_SPLITED
        ];

        // 支付状态 未支付
        $pay_status = [
            PS_PAYING,
            PS_UNPAYED
        ];

        $model = $model->whereIn('order_status', $order_status)
            ->whereIn('pay_status', $pay_status);

        $model = $model->with([
            'getPayment',
            'getSellerNegativeOrder'
        ]);

        $model = $model->first();

        $res = $model ? $model->toArray() : [];

        return $res;
    }

    /**
     * 获取发货订单详情
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getDeliveryOrderInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $res = DeliveryOrder::whereRaw(1);

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     * 获取订单数量
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getOrderCount($where = [])
    {
        if (empty($where)) {
            return 0;
        }

        $res = OrderInfo::whereRaw(1);

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        if (isset($where['order_sn'])) {
            $res = $res->where('order_sn', $where['order_sn']);
        }

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        if (isset($where['main_order_id'])) {
            $res = $res->where('main_order_id', $where['main_order_id']);
        }

        $count = $res->count();

        return $count;
    }

    /**
     * 获取订单商品信息
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getOrderGoodsInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $res = OrderGoods::whereRaw(1);

        if (isset($where['rec_id'])) {
            $res = $res->where('rec_id', $where['rec_id']);
        }

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        $res = $res->with([
            'getOrder' => function ($query) {
                $query->with([
                    'getRegionProvince' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionCity' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionDistrict' => function ($query) {
                        $query->select('region_id', 'region_name');
                    },
                    'getRegionStreet' => function ($query) {
                        $query->select('region_id', 'region_name');
                    }
                ]);
            }
        ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            /* 取得区域名 */
            $province = $res['get_order']['get_region_province']['region_name'] ?? '';
            $city = $res['get_order']['get_region_city']['region_name'] ?? '';
            $district = $res['get_order']['get_region_district']['region_name'] ?? '';
            $street = $res['get_order']['get_region_street']['region_name'] ?? '';
            $res['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;
        }

        return $res;
    }

    /**
     * 获取订单商品数量
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getOrderGoodsCount($where = [])
    {
        if (empty($where)) {
            return 0;
        }

        $res = OrderGoods::whereRaw(1);

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        if (isset($where['is_real'])) {
            $res = $res->where('is_real', $where['is_real']);
        }

        $count = $res->count();

        return $count;
    }

    /**
     * 获取订单列表
     *
     * @access  public
     * @param  $where
     * @return  array
     */
    public function getOrderList($where = [])
    {
        $res = OrderInfo::whereRaw(1);

        if (isset($where['order_id']) && !empty($where['order_id'])) {
            $where['order_id'] = !is_array($where['order_id']) ? explode(",", $where['order_id']) : $where['order_id'];

            $res = $res->whereIn('order_id', $where['order_id']);
        }

        if (isset($where['main_order_id']) && !empty($where['main_order_id'])) {
            $where['main_order_id'] = !is_array($where['main_order_id']) ? explode(",", $where['main_order_id']) : $where['main_order_id'];

            $res = $res->whereIn('order_id', $where['order_id']);
        }

        if (isset($where['order_sn']) && !empty($where['order_sn'])) {
            $where['order_sn'] = !is_array($where['order_sn']) ? explode(",", $where['order_sn']) : $where['order_sn'];

            $res = $res->whereIn('order_sn', $where['order_sn']);
        }

        if (isset($where['sort']) && isset($where['order'])) {
            $res = $res->orderBy($where['sort'], $where['order']);
        }

        if (isset($where['size'])) {
            if (isset($where['page'])) {
                $start = ($where['page'] - 1) * $where['size'];

                if ($start > 0) {
                    $res = $res->skip($start);
                }
            }

            if ($where['size'] > 0) {
                $res = $res->take($where['size']);
            }
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     * 获得订单地址信息
     *
     * @param int $order_id
     * @return string
     */
    public function getOrderUserRegion($order_id = 0)
    {

        /* 取得区域名 */
        $res = OrderInfo::where('order_id', $order_id);

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name as province_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name as city_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name as district_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name as street_name');
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        $region = '';
        if ($res) {
            $res = $res['get_region_province'] ? array_merge($res, $res['get_region_province']) : $res;
            $res = $res['get_region_city'] ? array_merge($res, $res['get_region_city']) : $res;
            $res = $res['get_region_district'] ? array_merge($res, $res['get_region_district']) : $res;
            $res = $res['get_region_street'] ? array_merge($res, $res['get_region_street']) : $res;


            $province_name = isset($res['province_name']) && $res['province_name'] ? $res['province_name'] : '';
            $city_name = isset($res['city_name']) && $res['city_name'] ? $res['city_name'] : '';
            $district_name = isset($res['district_name']) && $res['district_name'] ? $res['district_name'] : '';
            $street_name = isset($res['street_name']) && $res['street_name'] ? $res['street_name'] : '';

            $region = $province_name . " " . $city_name . " " . $district_name . " " . $street_name;
            $region = trim($region);
        }

        return $region;
    }

    /**
     * 获得门店订单信息
     *
     * @param array $where
     * @return array
     */
    public function getStoreOrderInfo($where = [])
    {
        $res = StoreOrder::whereRaw(1);

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /*
    * 订单队列（数据库版）先进先出
    */
    public function order_fifo($user_id, $cart_value)
    {
        $order_time = $this->timeRepository->getGmTime();
        $solve_time_start = $order_time - 30;
        $solve_time_end = $order_time + 30;
        $content = ['error' => 0];

        /* 添加数据，处理并发(库存)问题 start */
        $solve_order = [
            'user_id' => $user_id,
            'orec_id' => is_array($cart_value) ? implode(',', $cart_value) : $cart_value,
            'solve_type' => 0,//下单时
            'add_time' => $order_time
        ];
        SolveDealconcurrent::where('user_id', $user_id)->delete();//清除之前的队列
        SolveDealconcurrent::insert($solve_order);
        /* 添加数据，处理并发(库存)问题 end */

        /* 查询并发商品库存是否异常 */
        if ($cart_value && $this->config['stock_dec_time'] == SDT_PLACE) {
            $solve_info = SolveDealconcurrent::selectRaw("GROUP_CONCAT(id) AS solve_id, GROUP_CONCAT(orec_id) AS orec_id, count(*) AS solve_num")
                ->where('add_time', '>=', $solve_time_start)
                ->where('add_time', '<=', $solve_time_end)
                ->WHERE('solve_type', 0);
            $solve_info = $solve_info->first();
            $solve_info = $solve_info ? $solve_info->toArray() : [];

            if ($solve_info) {
                if ($solve_info['solve_num'] > 1) {
                    $ogoods_list = [];
                    $goods_orec = [];

                    $orec_goods = Cart::select('rec_id', 'goods_number', 'user_id', 'goods_id')
                        ->selectRaw("CONCAT(goods_id, '|', model_attr, '|', goods_attr_id) AS cart_info, CONCAT(goods_id, '|', model_attr, '|', goods_attr_id, '|', warehouse_id, '|', area_id, '|', area_city) AS warehouse_area")
                        ->where('is_real', 1)
                        ->whereIn('rec_type', [CART_GENERAL_GOODS, CART_GROUP_BUY_GOODS, CART_EXCHANGE_GOODS, CART_AUCTION_GOODS, CART_SNATCH_GOODS, CART_SECKILL_GOODS]);
                    $orec_goods = $orec_goods->get();
                    $orec_goods = $orec_goods ? $orec_goods->toArray() : [];

                    if ($orec_goods) {
                        foreach ($orec_goods as $cart_key => $cart_row) {
                            $ogoods_list[$cart_row['goods_id']][$cart_key] = $cart_row;
                            $orec_goods[$cart_key]['sd_id'] = SolveDealconcurrent::where('orec_id', $orec_goods[$cart_key]['rec_id'])->value('id');
                        }

                        foreach ($ogoods_list as $listkey => $listrow) {
                            foreach ($listrow as $key => $row) {
                                $goods_orec['cart_info'][$listkey][$key] = $row['cart_info'];
                                $goods_orec['warehouse_area'][$listkey][$key] = $row['warehouse_area'];
                            }
                        }

                        $ordinary_orec_list = $this->get_cart_goods_salve($goods_orec['cart_info'], $orec_goods);
                        $ordinary_solve_user = arr_foreach($ordinary_orec_list['no_solve']);

                        /* 删除并发商品队列数据 */
                        SolveDealconcurrent::where('solve_type', 0)
                            ->where('user_id', $user_id)
                            ->where('orec_id', $cart_value)
                            ->delete();

                        /* 并发处理判断当前用户库存不足，需返回购物车重新下单(不含仓库模式商品) */
                        if ($ordinary_solve_user && in_array($user_id, $ordinary_solve_user)) {
                            $content['error'] = 1;
                        }

                        $warehouse_orec_list = $this->get_cart_goods_salve($goods_orec['warehouse_area'], $orec_goods);
                        $warehouse_solve_user = arr_foreach($warehouse_orec_list['no_solve']);

                        /* 并发处理判断当前用户库存不足，需返回购物车重新下单(仓库地区模式商品) */
                        if ($warehouse_solve_user && in_array($user_id, $warehouse_solve_user)) {
                            $content['error'] = 1;
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * 检测并发商品的数据
     */
    public function get_cart_goods_salve($goods_orec, $orec_goods)
    {
        $orec_str = [];
        $is_solve_user = [];
        $no_solve_user = [];
        $cart_number = [];
        $goods_inventory = [];

        if ($goods_orec) {
            foreach ($goods_orec as $key => $row) {
                $orec_str[$key] = array_count_values($row);
                unset($row);
            }

            foreach ($orec_str as $key => $row) {
                $no_need = 0;
                foreach ($row as $k => $v) {
                    if ($v > 1) {
                        $ogoods = explode("|", $k);
                        $ogoods_id = isset($ogoods[0]) && !empty($ogoods[0]) ? $ogoods[0] : 0;
                        $omodel_attr = isset($ogoods[1]) && !empty($ogoods[1]) ? $ogoods[1] : 0;
                        $ogoods_attr_id = isset($ogoods[2]) && !empty($ogoods[2]) ? $ogoods[2] : '';

                        $owarehouse_id = isset($ogoods[3]) && !empty($ogoods[3]) ? $ogoods[3] : 0;
                        $oarea_id = isset($ogoods[4]) && !empty($ogoods[4]) ? $ogoods[4] : 0;
                        $oarea_city = isset($ogoods[5]) && !empty($ogoods[5]) ? $ogoods[5] : 0;

                        if (!empty($ogoods_attr_id)) {
                            /* 商品货品库存数量 */
                            $products = $this->goodsWarehouseService->getWarehouseAttrNumber($ogoods_id, $ogoods_attr_id, $owarehouse_id, $oarea_id, $oarea_city);
                            $goods_inventory[$key]['ogoods_number'] = !empty($products) ? $products['product_number'] : 0;
                        } else {
                            /* 商品库存数量 */
                            if ($omodel_attr == 1) {
                                $ogoods_number = WarehouseGoods::where('goods_id', $ogoods_id)->where('region_id', $owarehouse_id)->value('region_number');
                            } elseif ($omodel_attr == 2) {
                                $ogoods_number = WarehouseAreaGoods::where('goods_id', $ogoods_id)->where('region_id', $oarea_id);

                                if ($this->config['area_pricetype'] == 1) {
                                    $ogoods_number = $ogoods_number->where('city_id', $oarea_city);
                                }

                                $ogoods_number = $ogoods_number->value('region_number');
                            } else {
                                $ogoods_number = Goods::where('goods_id', $ogoods_id)->value('goods_number');
                            }

                            $goods_inventory[$key]['ogoods_number'] = $ogoods_number;
                        }
                    } else {
                        $no_need = 1;
                    }
                }
                if ($no_need == 0) {
                    /* 购物车商品购买数量 start */
                    foreach ($orec_goods as $orkey => $orrow) {
                        if ($orrow['goods_id'] == $key) {
                            if (isset($cart_number[$key]['cgoods_number'])) {
                                $cart_number[$key]['cgoods_number'] += $orrow['goods_number'];
                            } else {
                                $cart_number[$key]['cgoods_number'] = $orrow['goods_number'];
                            }
                        }
                    }
                    /* 购物车商品购买数量 end */
                }
            }
        }

        $orec_goods = $this->array_sort($orec_goods, 'sd_id');

        if ($cart_number) {
            krsort($goods_inventory);
            krsort($cart_number);

            foreach ($cart_number as $ckey => $crow) {
                $solve_number = $goods_inventory[$ckey]['ogoods_number'];

                if ($crow['cgoods_number'] > $solve_number) {
                    foreach ($orec_goods as $orkey => $orrow) {
                        if ($orrow['goods_id'] == $ckey) {
                            if ($orrow['goods_number'] <= $solve_number) {
                                $solve_number = $solve_number - $orrow['goods_number'];

                                /* 满足的并发(库存)条件 */
                                $is_solve_user[$ckey] = $orrow['user_id'];
                            } else {

                                /* 非满足的并发(库存)条件 */
                                $no_solve_user[$ckey] = $orrow['user_id'];
                            }
                        }
                    }
                }
            }
        }

        $arr = array(
            'is_solve' => $is_solve_user,
            'no_solve' => $no_solve_user
        );

        return $arr;
    }


    /*
    * 排序 （订单根据自增ID排序 先进先出）
    */
    public function array_sort($arr, $keys, $type = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

    /**
     * 生成查询订单总金额的字段
     * @param string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function orderAmountField($alias = '')
    {
        return " " . $alias . "goods_amount + " . $alias . "tax + " . $alias . "shipping_fee" .
            " + " . $alias . "insure_fee + " . $alias . "pay_fee + " . $alias . "pack_fee" .
            " + " . $alias . "card_fee ";
    }

    /**
     * 生成查询订单的sql
     * @param string $type 类型
     * @param string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function orderQuerySql($type = 'finished', $alias = '')
    {

        $where = '';

        /**
         * 已完成订单|finished
         * 已确认订单|queren
         * 已确认收货订单|confirm_take
         * 待确认收货订单|confirm_wait_goods
         * 待发货订单|await_ship
         * 待付款订单|await_pay
         * 未确认订单|unconfirmed
         * 未付款未发货订单：管理员可操作|unprocessed
         * 未付款未发货订单：管理员可操作|unpay_unship
         * 已发货订单：不论是否付款|shipped
         * 已付款订单：只要不是未发货（销量统计用）|real_pay
         */

        if ($type == 'finished') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5,
                defined(OS_RETURNED_PART) ? OS_RETURNED_PART : 7,
                defined(OS_ONLY_REFOUND) ? OS_ONLY_REFOUND : 8
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = [
                defined(SS_SHIPPED) ? SS_SHIPPED : 1,
                defined(SS_RECEIVED) ? SS_RECEIVED : 2
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            $pay_status = [
                defined(PS_PAYED) ? PS_PAYED : 2,
                defined(PS_PAYING) ? PS_PAYING : 1
            ];
            $pay_status = $this->baseRepository->getImplode($pay_status);

            return " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND " . $alias . "shipping_status IN (" . $shipping_status . ") " .
                " AND " . $alias . "pay_status  IN (" . $pay_status . ") ";
        } elseif ($type == 'queren') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5,
                defined(OS_SPLITING_PART) ? OS_SPLITING_PART : 6
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            return " AND " . $alias . "order_status IN (" . $order_status . ") ";
        }
        if ($type == 'confirm_take') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5,
                defined(OS_RETURNED_PART) ? OS_RETURNED_PART : 7,
                defined(OS_ONLY_REFOUND) ? OS_ONLY_REFOUND : 8
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = [
                defined(SS_RECEIVED) ? SS_RECEIVED : 2
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            $pay_status = [
                defined(PS_PAYED) ? PS_PAYED : 2
            ];
            $pay_status = $this->baseRepository->getImplode($pay_status);

            $return = " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND " . $alias . "shipping_status IN (" . $shipping_status . ") " .
                " AND " . $alias . "pay_status IN (" . $pay_status . ") ";

            return $return;
        }
        if ($type == 'confirm_wait_goods') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = [
                defined(SS_SHIPPED) ? SS_SHIPPED : 1
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            $pay_status = [
                defined(PS_PAYED) ? PS_PAYED : 2
            ];
            $pay_status = $this->baseRepository->getImplode($pay_status);

            return " AND " . $alias . "order_status IN (" . $order_status . ")" .
                " AND " . $alias . "shipping_status IN (" . $shipping_status . ")" .
                " AND " . $alias . "pay_status IN (" . $pay_status . ") ";
        } elseif ($type == 'await_ship') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5,
                defined(OS_SPLITING_PART) ? OS_SPLITING_PART : 6
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = [
                defined(SS_UNSHIPPED) ? SS_UNSHIPPED : 0,
                defined(SS_PREPARING) ? SS_PREPARING : 3,
                defined(SS_SHIPPED_ING) ? SS_SHIPPED_ING : 5
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            $pay_status = [
                defined(PS_PAYED) ? PS_PAYED : 2,
                defined(PS_PAYING) ? PS_PAYING : 1
            ];
            $pay_status = $this->baseRepository->getImplode($pay_status);

            $payList = $this->orderPaymentList(true);
            $payList = $this->baseRepository->getImplode($payList);

            if ($payList) {
                $where = " OR " . $alias . "pay_id IN (" . $payList . ")";
            }

            return " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND " . $alias . "shipping_status IN (" . $shipping_status . ") " .
                " AND (" . $alias . "pay_status IN (" . $pay_status . ")" . $where . ") ";
        } elseif ($type == 'await_pay') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = [
                defined(SS_SHIPPED) ? SS_SHIPPED : 1,
                defined(SS_RECEIVED) ? SS_RECEIVED : 2
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            $pay_status = defined(PS_UNPAYED) ? PS_UNPAYED : 0;

            $payList = $this->orderPaymentList(false);
            $payList = $this->baseRepository->getImplode($payList);

            if ($payList) {
                $where = " OR " . $alias . "pay_id IN (" . $payList . ")";
            }

            return " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND (" . $alias . "shipping_status IN (" . $shipping_status . ")" . $where . ") " .
                " AND " . $alias . "pay_status = '" . $pay_status . "'";
        } elseif ($type == 'unconfirmed') {

            $order_status = defined(OS_UNCONFIRMED) ? OS_UNCONFIRMED : 0;

            return " AND " . $alias . "order_status = '" . $order_status . "' ";
        } elseif ($type == 'unprocessed') {

            $order_status = [
                defined(OS_UNCONFIRMED) ? OS_UNCONFIRMED : 0,
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = defined(SS_UNSHIPPED) ? SS_UNSHIPPED : 0;

            $pay_status = defined(PS_UNPAYED) ? PS_UNPAYED : 0;

            return " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND " . $alias . "shipping_status = '" . $shipping_status . "'" .
                " AND " . $alias . "pay_status = '" . $pay_status . "' ";
        } elseif ($type == 'unpay_unship') {

            $order_status = [
                defined(OS_UNCONFIRMED) ? OS_UNCONFIRMED : 0,
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = [
                defined(SS_UNSHIPPED) ? SS_UNSHIPPED : 0,
                defined(SS_PREPARING) ? SS_PREPARING : 3
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            $pay_status = defined(PS_UNPAYED) ? PS_UNPAYED : 0;

            return " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND " . $alias . "shipping_status IN (" . $shipping_status . ") " .
                " AND " . $alias . "pay_status = '" . $pay_status . "' ";
        } elseif ($type == 'shipped') {

            $order_status = defined(OS_CONFIRMED) ? OS_CONFIRMED : 1;

            $shipping_status = [
                defined(SS_SHIPPED) ? SS_SHIPPED : 1,
                defined(SS_RECEIVED) ? SS_RECEIVED : 2
            ];
            $shipping_status = $this->baseRepository->getImplode($shipping_status);

            return " AND " . $alias . "order_status = '" . $order_status . "'" .
                " AND {$alias}shipping_status IN (" . $shipping_status . ") ";
        } elseif ($type == 'real_pay') {

            $order_status = [
                defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
                defined(OS_SPLITED) ? OS_SPLITED : 5,
                defined(OS_SPLITING_PART) ? OS_SPLITING_PART : 6,
                defined(OS_RETURNED_PART) ? OS_RETURNED_PART : 7
            ];
            $order_status = $this->baseRepository->getImplode($order_status);

            $shipping_status = defined(SS_UNSHIPPED) ? SS_UNSHIPPED : 0;

            $pay_status = [
                defined(PS_PAYED) ? PS_PAYED : 2,
                defined(PS_PAYING) ? PS_PAYING : 1
            ];
            $pay_status = $this->baseRepository->getImplode($pay_status);

            return " AND " . $alias . "order_status IN (" . $order_status . ") " .
                " AND " . $alias . "shipping_status <> " . $shipping_status .
                " AND " . $alias . "pay_status IN (" . $pay_status . ") ";
        }
    }

    /**
     * 取得支付方式id列表
     * @param bool $is_cod 是否货到付款
     * @return  array
     */
    public function orderPaymentList($is_cod)
    {
        $res = Payment::select('pay_id')->whereRaw(1);

        if ($is_cod) {
            $res = $res->where('is_cod', 1);
        } else {
            $res = $res->where('is_cod', 0);
        }

        $res = $this->baseRepository->getToArrayGet($res);
        $res = $this->baseRepository->getKeyPluck($res, 'pay_id');

        return $res;
    }

    /**
     * 订单列表数量
     * @param $uid
     * @return array
     */
    public function userOrderNum($uid)
    {
        $commentCount = $this->commentService->getUserOrderCommentCount($uid);

        $arr = [
            'all' => $this->getUserOrderCount($uid, 0), //订单数量
            'nopay' => $this->getUserOrderCount($uid, 1), //待付款订单数量
            'nogoods' => $this->getUserOrderCount($uid, 2), //待收货订单数量
            'team_num' => $this->teamUserOrderNum($uid),
            'not_comment' => $commentCount,  //待评价订单数量
            'return_count' => 0,
        ];

        return $arr;
    }

    /**
     * 订单数量
     * @param $uid
     * @param int $status
     * @return mixed
     */
    public function getUserOrderCount($uid, $status = 0)
    {
        $model = OrderInfo::where('main_count', 0)
            ->where('user_id', $uid)
            ->where('is_delete', 0)
            ->where('is_zc_order', 0); //排除众筹订单

        if ($status == 1) {
            // 待付款
            $model = $model->where('pay_status', PS_UNPAYED)
                ->whereNotIn('order_status', [OS_CANCELED, OS_INVALID, OS_RETURNED]);
        } elseif ($status == 2) {
            // 待收货
            $model = $model->where('pay_status', PS_PAYED)
                ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                ->where('shipping_status', '>=', SS_UNSHIPPED)
                ->where('shipping_status', '!=', SS_RECEIVED);
        }

        $order_count = $model->count();

        return $order_count;
    }

    /**
     * 统计拼团中数量
     */
    public function teamUserOrderNum($user_id)
    {
        $time = $this->timeRepository->getGmTime();

        $num = OrderInfo::where('user_id', $user_id)
            ->where('extension_code', 'team_buy')
            ->where('order_status', '<>', OS_CANCELED);

        $prefix = config('database.connections.mysql.prefix');

        $where = [
            'time' => $time,
            'prefix' => $prefix
        ];

        $num = $num->whereHas('getTeamLog', function ($query) use ($where) {
            $query->where('status', '<', 1)
                ->whereRaw("`" . $where['prefix'] . "team_log`.start_time + (SELECT `" . $where['prefix'] . "team_goods`.validity_time * 3600 FROM `" . $where['prefix'] . "team_goods` WHERE `" . $where['prefix'] . "team_goods`.id = `" . $where['prefix'] . "team_log`.t_id LIMIT 1) > " . $where['time'] .
                    " AND (SELECT COUNT(*) FROM `" . $where['prefix'] . "team_goods` WHERE `" . $where['prefix'] . "team_goods`.is_team = 1) > 0");
        });

        $num = $num->count();

        return $num;
    }

    /**发货单商品图片列表
     * @param string $invo
     * @return array
     */
    public function getDeliveryGoods($invo = '')
    {
        if (empty($invo)) {
            return [];
        }
        $delivery_id = DeliveryOrder::where('invoice_no', $invo)->value('delivery_id');

        $res = DeliveryGoods::where('delivery_id', $delivery_id)->with(['getGoods' => function ($query) {
            $query->select('goods_id', 'goods_thumb');
        }]);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);
                $row['goods_thumb'] = $row['goods_thumb'] ?? '';
                $arr[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
                $arr[$key]['goods_id'] = $row['goods_id'];
            }
        }

        return $arr;
    }

}
