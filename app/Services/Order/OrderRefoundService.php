<?php

namespace App\Services\Order;


use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\ReturnImages;
use App\Models\ValueCard;
use App\Models\ValueCardRecord;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;

class OrderRefoundService
{
    protected $commonRepository;
    protected $dscRepository;
    protected $baseRepository;

    public function __construct(
        CommonRepository $commonRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository
    )
    {
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
    }

    /**
     * 获取退换货图片列表
     *
     * @param array $where
     * @return mixed
     */
    public function getReturnImagesList($where = [])
    {
        $res = ReturnImages::whereRaw(1);

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        if (isset($where['rec_id'])) {
            $res = $res->where('rec_id', $where['rec_id']);
        }

        $res = $res->orderBy('id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $image) {
                $res[$key]['img'] = $image['img_file'];
                $res[$key]['img_file'] = $this->dscRepository->getImagePath($image['img_file']);
            }
        }

        return $res;
    }

    /**
     * 是否显示原路退款
     *
     * @param int $pay_id
     * @return bool
     */
    public function showReturnOnline($pay_id = 0)
    {
        if (empty($pay_id)) {
            return false;
        }

        $pay_arr = ['alipay', 'wxpay'];
        $pay_arr_not = ['balance', 'chunsejinrong'];
        $pay_code = Payment::where('pay_id', $pay_id)->where('enabled', 1)->where('is_online', 1)->value('pay_code');

        if (in_array($pay_code, $pay_arr) && !in_array($pay_code, $pay_arr_not)) {
            return true;
        }

        return false;
    }

    /**
     * 在线退款申请 （第三方在线支付)
     * 说明： 走退换货流程订单
     * @param string $return_sn
     * @param int $refund_amount
     * @return bool
     */
    public function refundApply($return_sn = '', $refund_amount = 0)
    {
        if (empty($return_sn)) {
            return false;
        }

        /**
         * 判断订单是否是在线支付 且 是否支付完成 已退款
         * 如果支付完成 则可以发起退款申请 接口
         * 如果成功，查询官方接口退款状态，更新网站订单退款状态
         */
        $model = OrderReturn::where('return_sn', $return_sn)->where('refound_status', 0);
        $model = $model->with([
            'orderInfo' => function ($query) {
                $query->select('order_id', 'order_sn', 'pay_id', 'pay_status', 'money_paid', 'referer');
            }
        ]);
        $model = $model->first();
        $return_order = $model ? $model->toArray() : [];

        if ($return_order) {
            $return_order = collect($return_order)->merge($return_order['order_info'])->except('order_info')->all();

            // 是否支持原路退款的 在线支付方式
            $can_refund = $this->showReturnOnline($return_order['pay_id']);
            if ($can_refund == true && $return_order['pay_status'] == PS_PAYED && $return_order['refound_status'] == 0) {

                $pay_code = Payment::where('pay_id', $return_order['pay_id'])->value('pay_code');
                $pay_code = $pay_code ? $pay_code : '';

                if ($pay_code && strpos($pay_code, 'pay_') === false) {
                    $payObject = $this->commonRepository->paymentInstance($pay_code);
                    if (!is_null($payObject) && is_callable([$payObject, 'refund'])) {
                        // 同意申请的同时提交退款申请到在线支付官方退款接口 等待结果
                        $return_order['should_return'] = $refund_amount > 0 ? $refund_amount : $return_order['should_return'];
                        return $payObject->refund($return_order);
                    }
                }
            }
        }

        return false;
    }

    /**
     * 支付原路退款
     * 说明：可以不用走退换货申请流程,但必须要有支付日志 pay_log  pay_trade_data
     *
     * @param array $refundOrder
     * $refundOrder = [
     *      'order_id' => '2018011111',
     *      'pay_id' => '1',
     *      'pay_status' => '2',
     *      'referer' => 'wxapp'
     * ];
     *
     * @param int $refund_amount
     * @return bool
     */
    public function refoundPay($refundOrder = [], $refund_amount = 0)
    {
        if (empty($refundOrder)) {
            return false;
        }

        // 是否支持原路退款的 在线支付方式
        $can_refund = $this->showReturnOnline($refundOrder['pay_id']);

        // 已支付订单
        if ($can_refund == true && $refundOrder['pay_status'] == PS_PAYED) {

            $pay_code = Payment::where('pay_id', $refundOrder['pay_id'])->value('pay_code');
            $pay_code = $pay_code ? $pay_code : '';

            if ($pay_code && strpos($pay_code, 'pay_') === false) {
                $payObject = $this->commonRepository->paymentInstance($pay_code);
                if (!is_null($payObject) && is_callable([$payObject, 'refund'])) {
                    // 同意申请的同时提交退款申请到在线支付官方退款接口 等待结果
                    $refundOrder['should_return'] = $refund_amount;
                    return $payObject->refund($refundOrder);
                }
            }
        }

        return false;
    }

    /**
     * 订单退款 如果使用储值卡 退还储值卡金额
     * @param int $order_id
     * @return int|mixed
     */
    public function returnValueCardMoney($order_id = 0)
    {
        $row = ValueCardRecord::where('order_id', $order_id)->first();
        $row = $row ? $row->toArray() : [];

        if ($row) {
            /* 更新储值卡金额 */
            ValueCard::where('vid', $row['vc_id'])->increment('card_money', $row['use_val']);

            /* 更新订单使用储值卡金额 */
            ValueCardRecord::where('vc_id', $row['vc_id'])->where('order_id', $order_id)->where('use_val', '>=', $row['use_val'])->decrement('use_val', $row['use_val']);

            return $row['use_val'];
        }

        return 0;
    }

    /**
     * 获取订单退款金额
     *
     * @param int $order_id
     * @return array
     */
    public function orderReturnAmount($order_id = 0)
    {

        $order_id = $this->baseRepository->getExplode($order_id);

        $return_amount = 0;
        $return_rate_price = 0;
        $ret_id = [];

        if ($order_id) {
            $row = OrderReturn::selectRaw("GROUP_CONCAT(ret_id) AS ret_id, SUM(actual_return) AS actual_return, SUM(return_rate_price) AS return_rate_price")
                ->whereIn('order_id', $order_id)
                ->whereIn('return_type', [1, 3])
                ->where('refound_status', 1);

            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) {

                $row['ret_id'] = $row['ret_id'] ?? [];
                $row['actual_return'] = $row['actual_return'] ?? 0;
                $row['return_rate_price'] = $row['return_rate_price'] ?? 0;

                $return_amount = $row['actual_return'] - $row['return_rate_price'];
                $return_rate_price = $row['return_rate_price'];
                $ret_id = $this->baseRepository->getExplode($row['ret_id']);
            }
        }

        $arr = [
            'return_amount' => $return_amount,
            'return_rate_price' => $return_rate_price,
            'ret_id' => $ret_id
        ];

        return $arr;
    }

    /**
     * 判断退款时需要退回的储值卡余额
     * @param float $refound_amount 退回的余额
     * @param float $should_return
     * @return array               储值卡数组
     */
    public function judgeValueCardMoney($refound_amount, $should_return, $order_id)
    {
        //查询出订单使用的储值卡金额
        $res = ValueCardRecord::where('order_id', $order_id);
        $value_card = $this->baseRepository->getToArrayFirst($res);
        //查询已经返还的储值卡金额
        $add_val = ValueCardRecord::where('order_id', $order_id)->sum('add_val');
        if ($value_card) {
            $value_card['use_val'] = $value_card['use_val'] - $add_val; //减去已经返还的金额
            if ($value_card['use_val'] > $should_return) {
                $value_card['use_val'] = $should_return;
            }
            if ($refound_amount < $value_card['use_val'] && empty($add_val)) {
                //退款金额小于储值卡金额
                $value_card['use_val'] = $refound_amount;
            }
        }
        return $value_card;
    }
}
