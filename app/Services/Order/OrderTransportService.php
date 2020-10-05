<?php

namespace App\Services\Order;

use App\Models\GoodsTransport;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\GoodsTransportTpl;
use App\Models\Shipping;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class OrderTransportService
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 运算商品详情设置非按区域运费模式价格
     *
     * @param $goods_list
     * @param array $consignee
     * @param int $shipping_id
     * @param string $shipping_code
     * @return array
     */
    public function getOrderTransport($goods_list, $consignee = [], $shipping_id = 0, $shipping_code = '')
    {
        $sprice = 0;
        $type_left = [];
        $freight = 0;

        if ($goods_list && $shipping_code != 'cac') {

            /**
             * 商品运费模板
             * 自定义
             */
            $custom_shipping = $this->getGoodsCustomShipping($goods_list);

            /**
             * 商品运费模板
             * 快递模板
             */
            $area_shipping = $this->getGoodsAreaShipping($goods_list, $shipping_id, $shipping_code, $consignee);

            foreach ($goods_list as $key => $row) {

                $row['is_shipping'] = $row['is_shipping'] ?? 0;

                if ($row['freight'] && $row['is_shipping'] == 0) {
                    if ($row['freight'] == 1) {
                        /**
                         * 商品
                         * 固定运费
                         */
                        $sprice += $row['shipping_fee'] * $row['goods_number'];
                    } else {

                        $trow = GoodsTransport::where('tid', $row['tid']);
                        $trow = $this->baseRepository->getToArrayFirst($trow);

                        if (isset($trow['freight_type']) && $trow['freight_type'] == 0) {
                            /**
                             * 商品
                             * 运费模板
                             * 区域运费
                             */
                            /* 运费模板配送方式 start */
                            $transport = ['top_area_id', 'area_id', 'tid', 'ru_id', 'sprice'];
                            $goods_transport = GoodsTransportExtend::select($transport)->where('ru_id', $row['ru_id'])->where('tid', $row['tid']);

                            $goods_transport = $goods_transport->whereRaw("(FIND_IN_SET('" . $consignee['city'] . "', area_id))");

                            $goods_transport = $this->baseRepository->getToArrayFirst($goods_transport);
                            /* 运费模板配送方式 end */

                            $goods_ship_transport = [];
                            if ($goods_transport) {
                                /* 运费模板配送方式 start */
                                $ship_transport = ['tid', 'ru_id', 'shipping_fee'];
                                $goods_ship_transport = GoodsTransportExpress::select($ship_transport)
                                    ->where('ru_id', $row['ru_id'])
                                    ->where('tid', $row['tid']);

                                $goods_ship_transport = $goods_ship_transport->whereRaw("(FIND_IN_SET('" . $shipping_id . "', shipping_id))");

                                $goods_ship_transport = $this->baseRepository->getToArrayFirst($goods_ship_transport);
                                /* 运费模板配送方式 end */
                            }

                            $goods_transport['sprice'] = isset($goods_transport['sprice']) ? $goods_transport['sprice'] : 0;
                            $goods_ship_transport['shipping_fee'] = isset($goods_ship_transport['shipping_fee']) ? $goods_ship_transport['shipping_fee'] : 0;

                            /* 是否免运费 start */
                            if ($custom_shipping && $custom_shipping[$row['tid']]['amount'] >= $trow['free_money'] && $trow['free_money'] > 0) {
                                $is_shipping = 1; /* 免运费 */
                            } else {
                                $is_shipping = 0; /* 有运费 */
                            }
                            /* 是否免运费 end */

                            if ($is_shipping == 0) {
                                if ($trow['type'] == 1) {
                                    $sprice += $goods_transport['sprice'] * $row['goods_number'] + $goods_ship_transport['shipping_fee'] * $row['goods_number'];
                                } else {
                                    $type_left[$row['tid']] = $goods_transport['sprice'] + $goods_ship_transport['shipping_fee'];
                                }
                            }
                        }
                    }
                } else {
                    //如果设置商品免运费,初始化运费模板所包含的运费
                    if ($row['is_shipping'] == 1) {
                        $area_shipping['shipping_fee'] = 0;
                    }

                    $freight += 1;
                }
            }

            $unified_total = $this->baseRepository->getArraySum($type_left);

            $arr = [
                'sprice' => $area_shipping['shipping_fee'] + $sprice + $unified_total, //固定运费 + 运费模板
                'freight' => $freight //是否有按配送区域计算运费的商品
            ];
        } else {
            $arr = [
                'sprice' => 0, //上门取货运费为0
                'freight' => $freight //是否有按配送区域计算运费的商品
            ];
        }

        return $arr;
    }

    /**
     * 商品运费模板[自定义]
     *
     * @param $goods_list
     * @return array
     */
    public function getGoodsCustomShipping($goods_list)
    {
        $tid_arr1 = [];
        $tid_arr2 = [];

        if ($goods_list) {
            foreach ($goods_list as $key => $row) {
                $tid_arr1[$row['tid']][$key] = $row;
            }

            foreach ($tid_arr1 as $key => $row) {
                $row = !empty($row) ? array_values($row) : $row;

                $tid_arr2[$key]['weight'] = 0;
                $tid_arr2[$key]['number'] = 0;
                $tid_arr2[$key]['amount'] = 0;
                foreach ($row as $gkey => $grow) {

                    $grow['goodsweight'] = $grow['goodsweight'] ?? 0;
                    $grow['goods_number'] = $grow['goods_number'] ?? 0;
                    $grow['goods_price'] = $grow['goods_price'] ?? 0;

                    $tid_arr2[$key]['weight'] += $grow['goodsweight'] * $grow['goods_number'];
                    $tid_arr2[$key]['number'] += $grow['goods_number'];
                    $tid_arr2[$key]['amount'] += $grow['goods_price'] * $grow['goods_number'];
                }
            }
        }

        return $tid_arr2;
    }

    /**
     * 商品运费模板[快递模板]
     *
     * @param array $goods_list
     * @param int $shipping_id
     * @param string $shipping_code
     * @param array $consignee
     * @return array
     */
    private function getGoodsAreaShipping($goods_list = [], $shipping_id = 0, $shipping_code = '', $consignee = [])
    {
        $tid_arr1 = [];
        $tid_arr2 = [];
        $shipping_fee = 0;

        if ($goods_list) {
            foreach ($goods_list as $key => $row) {
                $tid_arr1[$row['tid']][$key] = $row;
            }

            $tid_arr2 = [];
            foreach ($tid_arr1 as $key => $row) {
                $row = !empty($row) ? array_values($row) : $row;

                $tid_arr2[$key]['weight'] = 0;
                $tid_arr2[$key]['number'] = 0;
                $tid_arr2[$key]['amount'] = 0;
                foreach ($row as $gkey => $grow) {

                    $grow['goodsweight'] = $grow['goodsweight'] ?? 0;
                    $grow['goods_number'] = $grow['goods_number'] ?? 0;
                    $grow['goods_price'] = $grow['goods_price'] ?? 0;

                    $tid_arr2[$key]['weight'] += $grow['goodsweight'] * $grow['goods_number'];
                    $tid_arr2[$key]['number'] += $grow['goods_number'];
                    $tid_arr2[$key]['amount'] += $grow['goods_price'] * $grow['goods_number'];
                }
            }

            if (empty($shipping_id)) {
                $shipping_id = Shipping::where('shipping_code', $shipping_code)->value('shipping_id');
            }

            if (empty($shipping_code)) {
                $shipping_code = Shipping::where('shipping_id', $shipping_id)->value('shipping_code');
            }

            $region = [$consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'], $consignee['street']];
            $shipping_fee = 0;
            foreach ($tid_arr2 as $key => $row) {

                $trow = GoodsTransport::where('tid', $key);
                $trow = $this->baseRepository->getToArrayFirst($trow);

                if ($trow && $trow['freight_type'] == 1) {
                    $transport_tpl = GoodsTransportTpl::where('tid', $key)->where('shipping_id', $shipping_id);
                    $transport_tpl = $transport_tpl->whereRaw("((FIND_IN_SET('" . $region[1] . "', region_id)) OR (FIND_IN_SET('" . $region[2] . "', region_id) OR FIND_IN_SET('" . $region[3] . "', region_id) OR FIND_IN_SET('" . $region[4] . "', region_id)))");
                    $transport_tpl = $this->baseRepository->getToArrayFirst($transport_tpl);

                    $configure = !empty($transport_tpl) && $transport_tpl['configure'] ? unserialize($transport_tpl['configure']) : '';

                    if (!empty($configure)) {
                        $tid_arr2[$key]['shipping_fee'] = $this->dscRepository->shippingFee($shipping_code, $configure, $row['weight'], $row['amount'], $row['number']);
                    } else {
                        $tid_arr2[$key]['shipping_fee'] = 0;
                    }

                    $shipping_fee += $tid_arr2[$key]['shipping_fee'];
                }
            }
        }

        $arr = ['tid_list' => $tid_arr2, 'shipping_fee' => $shipping_fee];
        return $arr;
    }
}