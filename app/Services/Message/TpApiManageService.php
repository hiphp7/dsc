<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/20
 * Time: 14:28
 */

namespace App\Services\Message;


use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\ReturnGoods;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

class TpApiManageService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $config;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 快递鸟、电子面单：打印订单信息
     *
     * @param int $order_id
     * @param string $order_type
     * @return array
     */
    public function printOrderInfo($order_id = 0, $order_type = 'order')
    {
        if ($order_type == 'order') {
            //订单信息
            $order_info = OrderInfo::where('order_id', $order_id);
            $order_info = $this->baseRepository->getToArrayFirst($order_info);

            //商品数据
            $goods_list = OrderGoods::where('order_id', $order_id);

            $goods_list = $goods_list->with([
                'getGoods'
            ]);

            $goods_list = $this->baseRepository->getToArrayGet($goods_list);

            $table = 'goods';
        } else {

            //订单信息
            $order_info = WholesaleOrderInfo::where('order_id', $order_id);
            $order_info = $this->baseRepository->getToArrayFirst($order_info);

            //商品数据
            $goods_list = WholesaleOrderGoods::where('order_id', $order_id);

            $goods_list = $goods_list->with([
                'getWholesale'
            ]);

            $goods_list = $this->baseRepository->getToArrayGet($goods_list);

            $table = 'wholesale';
        }

        if (isset($order_info['order_id'])) {
            //补全信息开始

            /* 计算订单各种费用之和 */
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $order_info['total_fee'] = $order_info['goods_amount'] - $order_info['discount'] + $order_info['tax'] + $order_info['shipping_fee']
                    + $order_info['insure_fee'] + $order_info['pay_fee'] + $order_info['pack_fee'] + $order_info['card_fee']
                    + $order_info['rate_fee'];
            } else {
                $order_info['total_fee'] = $order_info['goods_amount'] - $order_info['discount'] + $order_info['tax'] + $order_info['shipping_fee']
                    + $order_info['insure_fee'] + $order_info['pay_fee'] + $order_info['pack_fee'] + $order_info['card_fee'];
            }

            //运费
            $order_info['formated_shipping_fee'] = $this->dscRepository->getPriceFormat($order_info['shipping_fee']);
            //订单总金额
            $order_info['formated_total_fee'] = $this->dscRepository->getPriceFormat($order_info['total_fee']);
            //商品总金额
            $order_info['formated_goods_amount'] = $this->dscRepository->getPriceFormat($order_info['goods_amount']);
            //保价费用
            $order_info['formated_insure_fee'] = $this->dscRepository->getPriceFormat($order_info['insure_fee']);
            //发票税额
            $order_info['formated_tax'] = $this->dscRepository->getPriceFormat($order_info['tax']);
            //支付手续费
            $order_info['formated_pay_fee'] = $this->dscRepository->getPriceFormat($order_info['pay_fee']);
            //优惠
            $order_info['formated_discount'] = $this->dscRepository->getPriceFormat($order_info['discount']);
            //余额
            $order_info['formated_surplus'] = $this->dscRepository->getPriceFormat($order_info['surplus']);
            //储值卡
            $order_info['formated_value_card'] = $this->dscRepository->getPriceFormat($order_info['card_fee']);
            //积分
            $order_info['formated_integral_money'] = $this->dscRepository->getPriceFormat($order_info['integral_money']);
            //店铺红包
            $order_info['formated_bonus'] = $this->dscRepository->getPriceFormat($order_info['bonus']);
            //优惠券
            $order_info['formated_coupons'] = $this->dscRepository->getPriceFormat($order_info['coupons']);

            //支付状态如果是0,1,3就是未付款
            if (in_array($order_info['pay_status'], [PS_UNPAYED, PS_PAYING, PS_PAYED_PART])) {
                //已付款金额
                $order_info['formated_money_paid'] = $this->dscRepository->getPriceFormat($order_info['money_paid']);
                //代付款总金额
                $order_info['formated_order_amount'] = $this->dscRepository->getPriceFormat($order_info['order_amount']);
            } else {
                $order_info['formated_money_paid'] = $this->dscRepository->getPriceFormat($order_info['money_paid']);
                $order_info['formated_order_amount'] = $this->dscRepository->getPriceFormat(0.00);
            }

            //补全信息结束

            /* 收货地址 */
            if ($order_type == 'order') {
                $province = Region::where('region_id', $order_info['province'])->value('region_name');
                $city = Region::where('region_id', $order_info['city'])->value('region_name');
                $district = Region::where('region_id', $order_info['district'])->value('region_name');

                $order_info['complete_address'] = $province . '-' . $city . '-' . $district . ' ' . $order_info['address'];
            } else {
                $order_info['complete_address'] = $order_info['address'];
            }

            $number_amount = 0;
            if ($goods_list) {
                foreach ($goods_list as $key => $val) {

                    $goods_list[$key]['format_goods_price'] = $this->dscRepository->getPriceFormat($val['goods_price']);
                    $goods_list[$key]['format_goods_amount'] = $this->dscRepository->getPriceFormat($val['goods_price'] * $val['goods_number']);

                    $products = [];
                    if ($order_type == 'order') {

                        //是否申请退货或者退款
                        $goods_list[$key]['is_return'] = ReturnGoods::where('rec_id', $val['rec_id'])->count();

                        $goods_list[$key]['shop_name'] = $this->merchantCommonService->getShopName($val['ru_id'], 1); //店铺名称

                        $goods_list[$key]['goods_thumb'] = isset($val['get_goods']['goods_thumb']) && $val['get_goods']['goods_thumb'] ? $this->dscRepository->getImagePath($val['get_goods']['goods_thumb']) : '';

                        $attr_arr = [];
                        //去掉复选属性by wu start
                        if (isset($val['goods_attr_id']) && $val['goods_attr_id']) {
                            if (strpos($val['goods_attr_id'], '|') !== false) {
                                $attr_arr = $this->baseRepository->getExplode($val['goods_attr_id'], '|');
                            } else {
                                $attr_arr = $this->baseRepository->getExplode($val['goods_attr_id']);
                            }
                        }

                        /* 普通商品 */
                        if ($val['model_attr'] == 1) {
                            $products = ProductsWarehouse::where('goods_id', $val['goods_id'])
                                ->where('warehouse_id', $val['warehouse_id']);
                        } elseif ($val['model_attr'] == 2) {
                            $products = ProductsArea::where('goods_id', $val['goods_id'])
                                ->where('area_id', $val['area_id']);

                            if ($this->config['area_pricetype'] == 1) {
                                $products = $products->where('city_id', $val['area_city']);
                            }
                        } else {
                            $products = Products::where('goods_id', $val['goods_id']);
                        }

                        //获取货品信息
                        if (!empty($attr_arr)) {
                            foreach ($attr_arr as $v) {
                                $products = $products->whereRaw("FIND_IN_SET('$v', REPLACE(goods_attr, '|', ','))");
                            }
                        }

                        $products = $this->baseRepository->getToArrayFirst($products);
                    } else {
                        $goods_list[$key]['goods_thumb'] = isset($val['get_wholesale']['goods_thumb']) && $val['get_wholesale']['goods_thumb'] ? $this->dscRepository->getImagePath($val['get_wholesale']['goods_thumb']) : '';
                    }

                    if (isset($products['bar_code'])) {
                        $bar_code = $products['bar_code'];
                    } else {
                        if ($table == 'wholesale') {
                            $bar_code = $val['get_wholesale']['bar_code'] ?? '';
                        } else {
                            $bar_code = $val['get_goods']['bar_code'] ?? '';
                        }
                        $bar_code = $bar_code ? $bar_code : '';
                    }

                    $goods_list[$key]['bar_code'] = $bar_code;
                    $number_amount += $val['goods_number'];
                }
            }
            $order_info['number_amount'] = $number_amount;
            $order_info['goods_list'] = $goods_list;
        }

        return $order_info;
    }
}
