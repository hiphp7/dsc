<?php

namespace App\Services\Shipping;

use App\Models\Cart;
use App\Models\GoodsTransport;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\Shipping;
use App\Models\ShippingPoint;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Order\OrderTransportService;


/**
 * Class ShippingService
 * @package App\Services\Shipping
 */
class ShippingService
{
    protected $config;
    protected $baseRepository;
    protected $sessionRepository;
    protected $dscRepository;
    protected $orderTransportService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        SessionRepository $sessionRepository,
        DscRepository $dscRepository,
        OrderTransportService $orderTransportService,
        TimeRepository $timeRepository
    )
    {
        $files = [
            'base',
            'common',
            'time',
        ];
        load_helper($files);

        $this->baseRepository = $baseRepository;
        $this->sessionRepository = $sessionRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderTransportService = $orderTransportService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 配送列表
     *
     * @param $rec_ids
     * @param $user_id
     * @param $ru_id
     * @param string $consignee
     * @param int $flow_type
     * @return mixed
     */
    public function getShippingList($rec_ids, $user_id, $ru_id, $consignee = '', $flow_type = 0)
    {
        $whereCart['flow_type'] = $flow_type;
        $whereCart['flow_consignee'] = $consignee;

        $ru_shipping = $this->getRuShippngInfo($rec_ids, $user_id, $ru_id, $consignee, $whereCart);

        $arr['shipping'] = $ru_shipping['shipping_list'];
        $arr['is_freight'] = $ru_shipping['is_freight'];
        $arr['shipping_rec'] = $ru_shipping['shipping_rec'];

        $arr['shipping_count'] = !empty($arr['shipping']) ? count($arr['shipping']) : 0;
        if (!empty($arr['shipping'])) {
            $arr['tmp_shipping_id'] = isset($arr['shipping'][0]['shipping_id']) ? $arr['shipping'][0]['shipping_id'] : 0; //默认选中第一个配送方式
            foreach ($arr['shipping'] as $kk => $vv) {
                if (isset($vv['default']) && $vv['default'] == 1) {
                    $arr['tmp_shipping_id'] = $vv['shipping_id'];
                    $arr['default_shipping'] = $vv;
                    continue;
                }
            }
        }

        return $arr;
    }

    /**
     * 查询商家默认配送方式
     *
     * @param $rec_ids
     * @param $user_id
     * @param $ru_id
     * @param string $consignee
     * @param array $whereCart
     * @return array
     */
    public function getRuShippngInfo($rec_ids, $user_id, $ru_id, $consignee = '', $whereCart = [])
    {
        //分离商家信息by wu start
        $cart_value_arr = [];
        $cart_freight = [];
        $shipping_rec = [];
        $freight = '';

        $rec_ids = $rec_ids ? explode(',', $rec_ids) : [];

        foreach ($rec_ids as $k => $v) {
            $cgv = Cart::select('rec_id', 'ru_id', 'tid', 'freight')->where('rec_id', $v)->first();
            $cgv = $cgv ? $cgv->toArray() : [];
            if ($cgv['ru_id'] != $ru_id) {
                unset($rec_ids[$k]);
            } else {
                $cart_value_arr[] = $cgv['rec_id'];

                if ($cgv['freight'] == 2) {
                    // 检测单个商品地区是否支持配送
                    if (empty($cgv['tid'])) {
                        $shipping_rec[] = $cgv['rec_id'];
                    }
                    @$cart_freight[$cgv['rec_id']][$cgv['freight']] = $cgv['tid'];
                }

                $freight .= $cgv['freight'] . ",";
            }
        }

        if ($freight) {
            $freight = $this->dscRepository->delStrComma($freight);
        }

        $is_freight = 0;
        if ($freight) {
            $freight = explode(",", $freight);
            $freight = array_unique($freight);

            /**
             * 判断是否有《地区运费》
             */
            if (in_array(2, $freight)) {
                $is_freight = 1;
            }
        }
        //分离商家信息by wu end

        if (!empty($user_id)) {
            $sess_id = " user_id = '$user_id' ";
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $sess_id = " session_id = '$session_id' ";
        }

        $order = flow_order_info($user_id);

        $seller_shipping = get_seller_shipping_type($ru_id);
        $shipping_id = $seller_shipping['shipping_id'] ?? 0;

        $consignee = isset($whereCart['flow_consignee']) ? $whereCart['flow_consignee'] : $consignee;
        $consignee['country'] = $consignee['country'] ?? 0;
        $consignee['province'] = $consignee['province'] ?? 0;
        $consignee['city'] = $consignee['city'] ?? 0;
        $consignee['district'] = $consignee['district'] ?? 0;
        $consignee['street'] = $consignee['street'] ?? 0;

        $region = [$consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'], $consignee['street']];

        $insure_disabled = true;
        $cod_disabled = true;


        // 查看购物车中是否全为免运费商品，若是则把运费赋为零
        $shipping_count = Cart::where('extension_code', '<>', 'package_buy')
            ->where('is_shipping', 0)
            ->where('ru_id', $ru_id)
            ->whereRaw($sess_id)
            ->whereIn('rec_id', $cart_value_arr)
            ->count();

        $shipping_list = [];

        if ($is_freight) {
            if ($cart_freight) {
                $list1 = [];
                $list2 = [];
                foreach ($cart_freight as $key => $row) {
                    if (isset($row[2]) && $row[2]) {
                        $transport_list = GoodsTransport::where('tid', $row[2])->get();
                        $transport_list = $transport_list ? $transport_list->toArray() : [];

                        if ($transport_list) {
                            foreach ($transport_list as $tkey => $trow) {
                                if ($trow['freight_type'] == 1) {
                                    $shipping_list1 = Shipping::select('shipping_id', 'shipping_code', 'shipping_name', 'shipping_order')->where('enabled', 1);
                                    $shipping_list1 = $shipping_list1->whereHas('getGoodsTransportTpl', function ($query) use ($region, $ru_id, $trow) {
                                        $query->whereRaw("(FIND_IN_SET('" . $region[1] . "', region_id) OR FIND_IN_SET('" . $region[2] . "', region_id) OR FIND_IN_SET('" . $region[3] . "', region_id) OR FIND_IN_SET('" . $region[4] . "', region_id))")
                                            ->where('user_id', $ru_id)
                                            ->where('tid', $trow['tid']);
                                    });
                                    $shipping_list1 = $this->baseRepository->getToArrayGet($shipping_list1);

                                    if (empty($shipping_list1)) {
                                        $shipping_rec[] = $key;
                                    }

                                    $list1[] = $shipping_list1;
                                } else {
                                    $shipping_list2 = GoodsTransportExpress::where('tid', $trow['tid'])->where('ru_id', $ru_id);

                                    $shipping_list2 = $shipping_list2->whereHas('getGoodsTransportExtend', function ($query) use ($ru_id, $trow, $region) {
                                        $query->where('ru_id', $ru_id)
                                            ->where('tid', $trow['tid'])
                                            ->whereRaw("((FIND_IN_SET('" . $region[1] . "', top_area_id)) OR (FIND_IN_SET('" . $region[2] . "', area_id) OR FIND_IN_SET('" . $region[3] . "', area_id) OR FIND_IN_SET('" . $region[4] . "', area_id)))");
                                    });

                                    $shipping_list2 = $this->baseRepository->getToArrayGet($shipping_list2);

                                    if ($shipping_list2) {
                                        $new_shipping = [];
                                        foreach ($shipping_list2 as $gtkey => $gtval) {
                                            $gt_shipping_id = !is_array($gtval['shipping_id']) ? explode(",", $gtval['shipping_id']) : $gtval['shipping_id'];
                                            $new_shipping[] = $gt_shipping_id ? $gt_shipping_id : [];
                                        }

                                        $new_shipping = $this->baseRepository->getFlatten($new_shipping);

                                        if ($new_shipping) {
                                            $shippingInfo = Shipping::select('shipping_id', 'shipping_code', 'shipping_name', 'shipping_order')
                                                ->where('enabled', 1)
                                                ->whereIn('shipping_id', $new_shipping);
                                            $list2[] = $this->baseRepository->getToArrayGet($shippingInfo);
                                        }
                                    } else {
                                        $shipping_rec[] = $key;
                                    }
                                }
                            }
                        }
                    }
                }

                $shipping_list1 = get_three_to_two_array($list1);
                $shipping_list2 = get_three_to_two_array($list2);

                if ($shipping_list1 && $shipping_list2) {
                    $shipping_list = array_merge($shipping_list1, $shipping_list2);
                } elseif ($shipping_list1) {
                    $shipping_list = $shipping_list1;
                } elseif ($shipping_list2) {
                    $shipping_list = $shipping_list2;
                }

                if ($shipping_list) {
                    //去掉重复配送方式 start
                    $new_shipping = [];
                    foreach ($shipping_list as $key => $val) {
                        @$new_shipping[$val['shipping_code']][] = $key;
                    }

                    foreach ($new_shipping as $key => $val) {
                        if (count($val) > 1) {
                            for ($i = 1; $i < count($val); $i++) {
                                unset($shipping_list[$val[$i]]);
                            }
                        }
                    }
                    //去掉重复配送方式 end

                    $shipping_list = get_array_sort($shipping_list, 'shipping_order');
                }
            }

            $configure_value = 0;
            $configure_type = 0;
            $shipping_fee = 0;

            if ($shipping_list) {
                $str_shipping = '';
                foreach ($shipping_list as $key => $row) {
                    $str_shipping .= $row['shipping_id'] . ",";
                }

                $str_shipping = $this->dscRepository->delStrComma($str_shipping);
                $str_shipping = explode(",", $str_shipping);
                if (in_array($shipping_id, $str_shipping)) {
                    $have_shipping = 1;
                } else {
                    $have_shipping = 0;
                }

                foreach ($shipping_list as $key => $val) {
                    if (substr($val['shipping_code'], 0, 5) != 'ship_') {
                        if ($this->config['freight_model'] == 0) {

                            /* 商品单独设置运费价格 start */
                            if ($rec_ids) {
                                if (count($rec_ids) == 1) {
                                    $cart_goods = Cart::where('rec_id', $rec_ids[0])->with(['getGoods' => function ($query) {
                                        $query->select('goods_id', 'goods_weight', 'shipping_fee');
                                    }])->get();
                                    $cart_goods = $cart_goods ? $cart_goods->toArray() : [];
                                    if ($cart_goods) {
                                        foreach ($cart_goods as $k => $v) {
                                            $cart_goods[$k]['goodsweight'] = $v['get_goods']['goods_weight'];
                                            $cart_goods[$k]['shipping_fee'] = $v['get_goods']['shipping_fee'];
                                        }
                                    }

                                    if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {
                                        if ($cart_goods[0]['freight'] == 1) {
                                            $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                                        } else {
                                            $trow = get_goods_transport($cart_goods[0]['tid']);

                                            if (isset($trow['freight_type']) && $trow['freight_type']) {
                                                $cart_goods[0]['user_id'] = $cart_goods[0]['ru_id'];
                                                $transport_tpl = get_goods_transport_tpl($cart_goods[0], $region, $val, $cart_goods[0]['goods_number']);

                                                $configure_value = isset($transport_tpl['shippingFee']) ? $transport_tpl['shippingFee'] : 0;
                                            } else {

                                                /**
                                                 * 商品运费模板
                                                 * 自定义
                                                 */
                                                $custom_shipping = $this->orderTransportService->getGoodsCustomShipping($cart_goods);
                                                $goods_transport = GoodsTransportExtend::select('top_area_id', 'area_id', 'tid', 'ru_id', 'sprice')
                                                    ->where('ru_id', $cart_goods[0]['ru_id'])
                                                    ->where('tid', $cart_goods[0]['tid'])->whereRaw("FIND_IN_SET(" . $consignee['city'] . ", area_id)");
                                                $goods_transport = $this->baseRepository->getToArrayFirst($goods_transport);

                                                $goods_ship_transport = GoodsTransportExpress::select('tid', 'ru_id', 'shipping_fee')
                                                    ->where('ru_id', $cart_goods[0]['ru_id'])
                                                    ->where('tid', $cart_goods[0]['tid'])
                                                    ->whereRaw("FIND_IN_SET(" . $val['shipping_id'] . ", shipping_id)");
                                                $goods_ship_transport = $this->baseRepository->getToArrayFirst($goods_ship_transport);

                                                $goods_transport['sprice'] = isset($goods_transport['sprice']) ? $goods_transport['sprice'] : 0;
                                                $goods_ship_transport['shipping_fee'] = isset($goods_ship_transport['shipping_fee']) ? $goods_ship_transport['shipping_fee'] : 0;

                                                /* 是否免运费 start */
                                                if ($custom_shipping && $custom_shipping[$cart_goods[0]['tid']]['amount'] >= $trow['free_money'] && $trow['free_money'] > 0) {
                                                    $is_shipping = 1; /* 免运费 */
                                                } else {
                                                    $is_shipping = 0; /* 有运费 */
                                                }
                                                /* 是否免运费 end */

                                                if ($is_shipping == 0) {
                                                    if ($trow['type'] == 1) {
                                                        $configure_value = $goods_transport['sprice'] * $cart_goods[0]['goods_number'] + $goods_ship_transport['shipping_fee'] * $cart_goods[0]['goods_number'];
                                                    } else {
                                                        $configure_value = $goods_transport['sprice'] + $goods_ship_transport['shipping_fee'];
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        /* 有配送按配送区域计算运费 */
                                        $configure_type = 1;
                                    }
                                } else {
                                    $cart_goods = Cart::whereRaw("rec_id" . db_create_in($rec_ids))->with(['getGoods' => function ($query) {
                                        $query->select('goods_id', 'goods_weight');
                                    }])->get();
                                    $cart_goods = $cart_goods ? $cart_goods->toArray() : [];

                                    if ($cart_goods) {
                                        foreach ($cart_goods as $k => $v) {
                                            $cart_goods[$k]['goodsweight'] = $v['get_goods']['goods_weight'];
                                        }
                                    }

                                    $order_transpor = $this->orderTransportService->getOrderTransport($cart_goods, $consignee, $val['shipping_id'], $val['shipping_code']);

                                    if (isset($order_transpor['freight']) && $order_transpor['freight']) {
                                        /* 有配送按配送区域计算运费 */
                                        $configure_type = 1;
                                    }

                                    $configure_value = isset($order_transpor['sprice']) ? $order_transpor['sprice'] : 0;
                                }
                            }
                            /* 商品单独设置运费价格 end */

                            $shipping_fee = $shipping_count == 0 ? 0 : $configure_value;
                            $shipping_list[$key]['free_money'] = $this->dscRepository->getPriceFormat(0, false);
                        }

                        if ($val['shipping_code'] == 'cac') {
                            $shipping_fee = 0;
                        }

                        $shipping_list[$key]['shipping_id'] = $val['shipping_id'];
                        $shipping_list[$key]['shipping_name'] = $val['shipping_name'];
                        $shipping_list[$key]['shipping_code'] = $val['shipping_code'];
                        $shipping_list[$key]['format_shipping_fee'] = $this->dscRepository->getPriceFormat($shipping_fee, false);
                        $shipping_list[$key]['shipping_fee'] = $shipping_fee;

                        if (isset($val['insure']) && $val['insure']) {
                            $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? $this->dscRepository->getPriceFormat($val['insure'], false) : $val['insure'];
                        }

                        /* 当前的配送方式是否支持保价 */
                        if ($val['shipping_id'] == $order['shipping_id']) {
                            if (isset($val['insure']) && $val['insure']) {
                                $insure_disabled = ($val['insure'] == 0);
                            }
                            if (isset($val['support_cod']) && $val['support_cod']) {
                                $cod_disabled = ($val['support_cod'] == 0);
                            }
                        }

                        //默认配送方式
                        if ($have_shipping == 1) {
                            $shipping_list[$key]['default'] = 0;
                            if ($shipping_id == $val['shipping_id']) {
                                $shipping_list[$key]['default'] = 1;
                            }
                        } else {
                            if ($key == 0) {
                                $shipping_list[$key]['default'] = 1;
                            }
                        }

                        $shipping_list[$key]['insure_disabled'] = $insure_disabled;
                        $shipping_list[$key]['cod_disabled'] = $cod_disabled;
                    }

                    // 兼容过滤ecjia配送方式
                    if (substr($val['shipping_code'], 0, 5) == 'ship_') {
                        unset($shipping_list[$key]);
                    }
                }

                //去掉重复配送方式 by wu start
                $shipping_type = [];
                foreach ($shipping_list as $key => $val) {
                    @$shipping_type[$val['shipping_code']][] = $key;
                }

                foreach ($shipping_type as $key => $val) {
                    if (count($val) > 1) {
                        for ($i = 1; $i < count($val); $i++) {
                            unset($shipping_list[$val[$i]]);
                        }
                    }
                }
                //去掉重复配送方式 by wu end
            }
        } else {
            $configure_value = 0;

            /* 商品单独设置运费价格 start */
            if ($rec_ids) {
                if (count($rec_ids) == 1) {
                    $cart_goods = Cart::where('rec_id', $rec_ids[0])->with(['getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_weight');
                    }])->get();
                    $cart_goods = $cart_goods ? $cart_goods->toArray() : [];
                    if ($cart_goods) {
                        foreach ($cart_goods as $k => $v) {
                            $cart_goods[$k]['goodsweight'] = $v['get_goods']['goods_weight'];
                        }
                    }

                    if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {
                        $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                    } else {
                        /* 有配送按配送区域计算运费 */
                        $configure_type = 1;
                    }
                } else {
                    $cart_goods = Cart::whereRaw("rec_id" . db_create_in($rec_ids))->with(['getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_weight');
                    }])->get();
                    $cart_goods = $cart_goods ? $cart_goods->toArray() : [];
                    if ($cart_goods) {
                        foreach ($cart_goods as $k => $v) {
                            $cart_goods[$k]['goodsweight'] = $v['get_goods']['goods_weight'];
                        }
                    }

                    $sprice = 0;
                    foreach ($cart_goods as $key => $row) {
                        if ($row['is_shipping'] == 0) {
                            $sprice += $row['shipping_fee'] * $row['goods_number'];
                        }
                    }

                    $configure_value = $sprice;
                }
            }
            /* 商品单独设置运费价格 end */

            $shipping_fee = $shipping_count == 0 ? 0 : $configure_value;
            // 上门自提免配送费
            if (isset($seller_shipping['shipping_code']) && $seller_shipping['shipping_code'] == 'cac') {
                $shipping_fee = 0;
            }
            $shipping_list[0]['free_money'] = $this->dscRepository->getPriceFormat(0, false);
            $shipping_list[0]['format_shipping_fee'] = $this->dscRepository->getPriceFormat($shipping_fee, false);
            $shipping_list[0]['shipping_fee'] = $shipping_fee;
            $shipping_list[0]['shipping_id'] = isset($seller_shipping['shipping_id']) && !empty($seller_shipping['shipping_id']) ? $seller_shipping['shipping_id'] : 0;
            $shipping_list[0]['shipping_name'] = isset($seller_shipping['shipping_name']) && !empty($seller_shipping['shipping_name']) ? $seller_shipping['shipping_name'] : '';
            $shipping_list[0]['shipping_code'] = isset($seller_shipping['shipping_code']) && !empty($seller_shipping['shipping_code']) ? $seller_shipping['shipping_code'] : '';
            $shipping_list[0]['default'] = 1;
        }

        //在shipping数组中增加税费字段，切换配送方式时调用
        if (CROSS_BORDER === true) // 跨境多商户
        {
            $web = app(CrossBorderService::class)->webExists();

            if (!empty($web)) {
                $shipping_list = $web->countshippingRate($rec_ids, $shipping_list);
            }
        }


        return ['is_freight' => $is_freight, 'shipping_list' => $shipping_list, 'shipping_rec' => $shipping_rec];
    }

    /**
     * 重新组合购物流程商品数组
     */
    public function get_new_group_cart_goods($cart_goods_list_new)
    {
        $car_goods = [];
        foreach ($cart_goods_list_new as $key => $goods) {
            foreach ($goods['goods_list'] as $k => $list) {
                $car_goods[] = $list;
            }
        }

        return $car_goods;
    }

    /**
     *  区域获得自提点
     * @param type $district
     */
    public function getSelfPoint($district, $point_id = 0, $limit = 100, $ru_id = 0)
    {
        $where = "";
        $shipping_dateStr = '';

        $list = ShippingPoint::from('shipping_point as sp')
            ->select('ar.shipping_area_id', 'ar.region_id', 'sp.id as point_id', 'sp.name', 'sp.mobile', 'sp.address', 'sp.anchor', 'sa.shipping_id', 'ss.shipping_code', 'ss.shipping_name', 'cr.parent_id as city')
            ->leftjoin('area_region as ar', 'ar.shipping_area_id', '=', 'sp.shipping_area_id')
            ->leftjoin('shipping_area as sa', 'sa.shipping_area_id', '=', 'sp.shipping_area_id')
            ->leftjoin('shipping as ss', 'ss.shipping_id', '=', 'sa.shipping_id')
            ->leftjoin('region as cr', 'cr.region_id', '=', 'ar.region_id');

        if ($point_id > 0) {
            $list = $list->where('sp.id', $point_id);
        } else {
            $list = $list->where('ar.region_id', $district);
        }
        $list = $list->limit($limit);

        $list = $this->baseRepository->getToArrayGet($list);

        foreach ($list as $key => $val) {
            if ($point_id > 0 && $val['point_id'] == $point_id) {
                $list[$key]['is_check'] = 1;
            }
            if ($shipping_dateStr) {
                $list[$key]['shipping_dateStr'] = $shipping_dateStr;
            } else {
                $list[$key]['shipping_dateStr'] = $this->timeRepository->getLocalDate("m", $this->timeRepository->getLocalStrtoTime(' +1day')) . "月" . $this->timeRepository->getLocalDate("d", $this->timeRepository->getLocalStrtoTime(' +1day')) . "日&nbsp;【周" . $this->timeRepository->transitionDate($this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime(' +1day'))) . "】";
            }
        }

        return $list;
    }
}
