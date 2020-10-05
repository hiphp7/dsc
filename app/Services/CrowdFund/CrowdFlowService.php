<?php

namespace App\Services\CrowdFund;

use App\Models\OrderInfo;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\ZcGoods;
use App\Models\ZcProject;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 众筹订单提交
 * Class CrowdFund
 * @package App\Services
 */
class CrowdFlowService
{
    protected $timeRepository;
    protected $config;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 购物车商品
     * @access  public
     * @return  $pid  项目id
     * @return  $id   方案id
     */
    public function getCartGoods($pid = 0, $id = 0, $number = 0)
    {
        $gmtime = $this->timeRepository->getGmTime();

        $res = ZcProject::from('zc_project as zp')
            ->select('zp.id', 'zp.title', 'zp.init_id', 'zp.start_time', 'zp.end_time', 'zp.end_time', 'zp.amount', 'zp.join_money', 'zp.join_num', 'zp.focus_num', 'zp.prais_num', 'zp.title_img', 'zg.price', 'zg.content', 'zg.limit', 'zg.backer_num', 'zg.shipping_fee')
            ->leftjoin('zc_goods as zg', 'zg.pid', '=', 'zp.id')
            ->where('zg.id', $id)
            ->where('zg.pid', $pid)
            ->first();
        $res = $res ? $res->toArray() : [];

        $timeFormat = $this->config['time_format'];
        $list = [];
        if ($res) {
            $list['id'] = $res['id'];
            $list['title'] = $res['title'];
            $list['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $res['start_time']);
            $list['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $res['end_time']);
            $list['amount'] = $res['amount'];
            $list['formated_amount'] = $this->dscRepository->getPriceFormat($res['amount'], false);
            $list['join_money'] = $res['join_money'];
            $list['formated_join_money'] = $this->dscRepository->getPriceFormat($res['join_money'], false);
            $list['join_num'] = $res['join_num'];
            $shenyu_time = $res['end_time'] - $gmtime;
            $list['shenyu_time'] = ceil($shenyu_time / 3600 / 24);
            $list['baifen_bi'] = round($res['join_money'] / $res['amount'], 2) * 100;
            $list['title_img'] = $this->dscRepository->getImagePath($res['title_img']);
            $list['price'] = $res['price'];
            $list['formated_price'] = $this->dscRepository->getPriceFormat($res['price'], false);
            $list['content'] = $res['content'];
            $list['shipping_fee'] = $res['shipping_fee'];
            $list['limit'] = $res['limit'];
            $list['backer_num'] = $res['backer_num'];
            $list['number'] = $number;
        }

        return $list;
    }

    /**
     * 活动默认配送方式
     * @access  public
     * @return  $pid  项目id
     * @return  $id   方案id
     */
    public function getSellerShopinfoShipping($ru_id = 0)
    {
        return SellerShopinfo::where('ru_id', $ru_id)->value('shipping_id');
    }


    /**
     * 配送方式信息
     * @access  public
     * @return  $pid  项目id
     * @return  $id   方案id
     */
    public function getShippingInfo($shipping_id = 0)
    {
        $shipping = Shipping::select('shipping_id', 'shipping_name', 'support_cod')->where('shipping_id', $shipping_id)->first();

        return $shipping ? $shipping->toArray() : [];
    }


    /**
     * 计算订单的费用
     * @access  public
     * @return  $pid  项目id
     * @return  $id   方案id
     */
    public function getOrderFee($goods = [])
    {
        $total = [
            'goods_price' => 0,
            'shipping_fee' => 0,
            'amount' => 0
        ];

        /* 商品总价 */
        $cat_goods = [0 => $goods];
        foreach ($cat_goods as $val) {
            $total['goods_price'] += $val['price'] * $val['number'];
        }

        // 配送费用
        $total['shipping_fee'] = $goods['shipping_fee'] && $goods['shipping_fee'] > 0 ? $goods['shipping_fee'] : 0;

        // 计算订单总额
        $total['amount'] = $total['goods_price'] + $total['shipping_fee'];

        $total['shipping_fee'] = $goods['shipping_fee'] ? $this->dscRepository->getPriceFormat($goods['shipping_fee'], false) : 0;
        // 格式化订单总额
        $total['amount_formated'] = $this->dscRepository->getPriceFormat($total['amount'], false);

        return $total;
    }


    /**
     * 判断重复商品订单 是否支付
     * @access  public
     * @return  $pid  项目id
     * @return  $id   方案id
     */
    public function getZcOrderNum($user_id = 0, $id = 0)
    {
        $count = OrderInfo::where('user_id', $user_id)
            ->where('zc_goods_id', $id)
            ->where('is_zc_order', 1)
            ->where('is_delete', 0)
            ->where('pay_status', 0);
        $count = $count->where(function ($query){
            $query->where('order_status', '<>', 2)
                ->orWhere('order_status', '<>', 3);
        });

        $count = $count->count('order_id');

        return $count;
    }


    /**
     * 付款更新众筹信息
     */
    public function updateZcProject($order_id = 0)
    {
        //取得订单信息
        $order_info = OrderInfo::select('user_id', 'is_zc_order', 'zc_goods_id')->where('order_id', $order_id)->first();
        $order_info = $order_info ? $order_info->toArray() : [];
        if ($order_info) {
            $user_id = $order_info['user_id'];
            $is_zc_order = $order_info['is_zc_order'];
            $zc_goods_id = $order_info['zc_goods_id'];

            if ($is_zc_order == 1 && $zc_goods_id > 0) {
                //获取众筹商品信息
                $zc_goods_info = ZcGoods::where('id', $zc_goods_id)->first();
                $zc_goods_info = $zc_goods_info ? $zc_goods_info->toArray() : [];
                $pid = $zc_goods_info['pid'];
                $goods_price = $zc_goods_info['price'];
                $backer_list = $zc_goods_info['backer_list'];

                if (empty($backer_list)) {
                    $backer_list = $user_id;
                } else {
                    $backer_list = $backer_list . ',' . $user_id;
                }

                //增加众筹商品支持的用户数量,支持的用户id
                ZcGoods::where('id', $zc_goods_id)
                    ->update(['backer_num' => $zc_goods_info['backer_num'] + 1, 'backer_list' => $backer_list]);

                //增加众筹项目的支持用户总数量、增加众筹项目总金额
                $zc_project = ZcProject::where('id', $pid)
                    ->first()
                    ->toArray();

                ZcProject::where('id', $pid)
                    ->update(['join_num' => $zc_project['join_num'] + 1, 'join_money' => $zc_project['join_money'] + $goods_price]);
            }
        }
    }


    /**
     *  插入订单
     */
    public function addOrderInfo($order_id)
    {
        $order_id = OrderInfo::insertGetId($order_id);

        return $order_id;
    }
}
