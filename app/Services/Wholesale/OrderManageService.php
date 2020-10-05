<?php

namespace App\Services\Wholesale;

use App\Models\Suppliers;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Payment\PaymentService;

class OrderManageService
{
    protected $baseRepository;
    protected $paymentService;
    protected $config;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        PaymentService $paymentService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->paymentService = $paymentService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 生成查询订单总金额的字段
     * @param string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function orderAmountField($alias = '')
    {
        return "   {$alias}goods_amount + {$alias}tax" .
            " + {$alias}pay_fee";
    }

    /**
     * 查询订单商家ID
     *
     * @param int $order_id
     * @return mixed
     */
    public function orderSellerId($order_id = 0)
    {
        $suppliers_id = WholesaleOrderInfo::where('order_id', $order_id)->value('suppliers_id');
        $suppliers_id = $suppliers_id ? $suppliers_id : 0;

        $user_id = Suppliers::where('suppliers_id', $suppliers_id)->value('user_id');
        $user_id = $user_id ? $user_id : 0;

        return $user_id;
    }

    //根据订单商品查询批发商品信息
    public function wholesaleOrderGoodsToInfo($order_id = 0)
    {
        $res = WholesaleOrderGoods::where('order_id', $order_id);

        $res = $res->with([
            'getWholesaleOrderInfo',
            'getWholesale'
        ]);
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $goods = $row['get_wholesale'];
                $order = $row['get_wholesale_order_info'];

                $res[$key]['extension_name'] = $row['goods_name'];
                $res[$key]['order_sn'] = $order['order_sn'];
                $res[$key]['url'] = $this->dscRepository->buildUri('wholesale_goods', ['aid' => $row['goods_id']], $row['goods_name']);
                $res[$key]['goods_thumb'] = isset($goods['goods_thumb']) && $goods['goods_thumb'] ? $this->dscRepository->getImagePath($goods['goods_thumb']) : '';
            }
        }

        return $res;
    }

    /**
     * 取得采购订单信息
     * @param int $order_id 订单id（如果order_id > 0 就按id查，否则按sn查）
     * @param string $order_sn 订单号
     * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    public function wholesaleOrderInfo($order_id = 0, $order_sn = '')
    {
        /* 计算订单各种费用之和的语句 */
        $order_id = intval($order_id);

        $res = WholesaleOrderInfo::whereRaw(1);

        if ($order_id > 0) {
            $res = $res->where('order_id', $order_id);
        } else {
            $res = $res->where('order_sn', $order_sn);
        }

        $res = $res->with([
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

        $order = $this->baseRepository->getToArrayFirst($res);

        /* 格式化金额字段 */
        if ($order) {
            $conv_adjust_fee = $order['adjust_fee'] > 0 ? $order['adjust_fee'] : -$order['adjust_fee'];
            $order['formated_adjust_fee'] = $this->dscRepository->getPriceFormat($conv_adjust_fee, false);
            $order['formated_goods_amount'] = $this->dscRepository->getPriceFormat($order['goods_amount'], false);
            $order['formated_order_amount'] = $this->dscRepository->getPriceFormat(abs($order['order_amount']), false);
            $order['formated_add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $order['add_time']);

            $ru_id = $this->orderSellerId($order['order_id']);
            $order['ru_id'] = $ru_id;

            $where = [
                'pay_id' => $order['pay_id']
            ];

            $payment = $this->paymentService->getPaymentInfo($where);

            $order['pay_name'] = $payment['pay_name'];
            $order['pay_code'] = $payment['pay_code'];
            $order['pay_time'] = $order['pay_time'] ? $this->timeRepository->getLocalDate($this->config['time_format'], $order['pay_time']) : 0;
            $order['shipping_time'] = $order['shipping_time'] ? $this->timeRepository->getLocalDate($this->config['time_format'], $order['shipping_time']) : 0;

            /* 取得区域名 */
            $province = $order['get_region_province']['region_name'] ?? '';
            $city = $order['get_region_city']['region_name'] ?? '';
            $district = $order['get_region_district']['region_name'] ?? '';
            $street = $order['get_region_street']['region_name'] ?? '';
            $order['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $order['mobile'] = $this->dscRepository->stringToStar($order['mobile']);
                $order['tel'] = $this->dscRepository->stringToStar($order['tel']);
                $order['email'] = $this->dscRepository->stringToStar($order['email']);
            }
        }

        return $order;
    }

    /**
     * 订单结算金额
     *
     * @param int $order_id
     * @return mixed
     */
    public function getSuppliersSettlementAmount($order_id = 0)
    {
        $order_info = WholesaleOrderInfo::where('order_id', $order_id);
        $order_info = $this->baseRepository->getToArrayFirst($order_info);

        if ($order_info) {
            $suppliers_percent = Suppliers::where('suppliers_id', $order_info['suppliers_id'])->value('suppliers_percent');
            $suppliers_percent = empty($suppliers_percent) ? 1 : $suppliers_percent / 100;//如果比例为0的话 默认为1

            $effective_amount_into = $order_info['order_amount'] - $order_info['return_amount']; //有效结算
            $effective_amount_into = $effective_amount_into > 0 ? $effective_amount_into : 0;

            $brokerage_amount = $effective_amount_into * $suppliers_percent; //应结金额

            $order_info['suppliers_percent'] = $suppliers_percent * 100;
            $order_info['brokerage_amount'] = $brokerage_amount;
        }

        return $order_info;
    }

    /**
     * 生成查询订单的sql
     * @param string $type 类型
     * @param string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function wholesaleOrderQuerySql($type = 'finished', $alias = '')
    {
        /* 退货订单 */
        if ($type == 'return_order') {
            return " AND {$alias}order_status " . db_create_in(array(4)) .
                " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, 0)) .
                " AND {$alias}pay_status " . db_create_in(array(PS_PAYED, 4, 5)) . " ";
        } /* 已完成订单 */
        elseif ($type == 'finished') {
            return " AND {$alias}order_status " . db_create_in(array(1)) .
                " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
                " AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
        } /* 已确认订单 ecmoban zhou */
        elseif ($type == 'queren') {
            return " AND   {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) . " ";
        } /* 已确认收货订单 bylu */
        elseif ($type == 'confirm_take') {
            return " AND {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_RETURNED_PART, OS_SPLITED)) .
                " AND {$alias}shipping_status " . db_create_in(array(SS_RECEIVED)) .
                " AND {$alias}pay_status " . db_create_in(array(PS_PAYED)) . " ";
        } /* 待确认收货订单 */
        elseif ($type == 'confirm_wait_goods') {
            return " AND {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
                " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED)) .
                " AND {$alias}pay_status " . db_create_in(array(PS_PAYED)) . " ";
        } /* 待发货订单 */
        elseif ($type == 'await_ship') {

            $payList = $this->paymentService->paymentIdList(true);

            return " AND   {$alias}order_status " .
                db_create_in(array(0)) .
                " AND   {$alias}shipping_status " .
                db_create_in(array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) .
                " AND ( {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " OR {$alias}pay_id " . db_create_in($payList) . ") ";
        } /* 待付款订单 */
        elseif ($type == 'await_pay') {

            $payList = $this->paymentService->paymentIdList(false);

            return " AND   {$alias}order_status " . db_create_in(array(0)) .
                " AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
                " AND ( {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR {$alias}pay_id " . db_create_in($payList) . ") ";
        } /* 未确认订单 */
        elseif ($type == 'unconfirmed') {
            return " AND {$alias}order_status = '" . OS_UNCONFIRMED . "' ";
        } /* 未处理订单：用户可操作 */
        elseif ($type == 'unprocessed') {
            return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
                " AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
                " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
        } /* 未付款未发货订单：管理员可操作 */
        elseif ($type == 'unpay_unship') {
            return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
                " AND {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
                " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
        } /* 已发货订单：不论是否付款 */
        elseif ($type == 'shipped') {
            return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
                " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " ";
        } /* 已付款订单：只要不是未发货（销量统计用） */
        elseif ($type == 'real_pay') {
            return " AND {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART, OS_SPLITED, OS_RETURNED_PART)) .
                " AND {$alias}shipping_status <> " . SS_UNSHIPPED .
                " AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
        } else {
            die('函数 order_query_sql 参数错误');
        }
    }
}
