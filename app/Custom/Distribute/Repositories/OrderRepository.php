<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\AutoSms;
use App\Models\CouponsUser;
use App\Models\Crons;
use App\Models\MerchantsShopInformation;
use App\Models\OrderAction;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\PayLog;
use App\Models\ReturnAction;
use App\Models\SellerBillOrder;
use App\Models\SellerShopinfo;
use App\Models\ValueCardRecord;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\ConfigService;
use App\Services\User\RefoundService;

/**
 * Class OrderRepository
 * @package App\Custom\Distribute\Repositories
 */
class OrderRepository
{
    protected $timeRepository;
    protected $dscRepository;
    protected $orderRefundRepository;
    protected $configService;
    protected $baseRepository;
    protected $refoundService;
    protected $accountLogRepository;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        OrderRefundRepository $orderRefundRepository,
        ConfigService $configService,
        BaseRepository $baseRepository,
        RefoundService $refoundService,
        AccountLogRepository $accountLogRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->orderRefundRepository = $orderRefundRepository;
        $this->configService = $configService;
        $this->baseRepository = $baseRepository;
        $this->refoundService = $refoundService;
        $this->accountLogRepository = $accountLogRepository;
    }

    /**
     * 获取订单信息
     * @param int $order_id
     * @param array $columns
     * @return string
     */
    public function getOrderInfo($order_id = 0, $columns = ['*'])
    {
        $model = OrderInfo::select($columns)->where('order_id', $order_id);

        $model = $model->with([
            'goods',
        ]);

        $order = $model->first();

        $order = $order ? $order->toArray() : [];

        return $order;
    }

    /**
     * 用户订单详情
     * @param int $order_id
     * @param int $user_id
     * @return array|mixed
     */
    public function userOrderDetail($order_id = 0, $user_id = 0)
    {
        $model = OrderInfo::where('user_id', $user_id)
            ->where('order_id', $order_id);

        $model = $model->with([
            'goods' => function ($query) {
                $query->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_thumb', 'goods_img');
                    }
                ]);
            },
        ]);

        $order = $model->first();

        return $order ? $order->toArray() : [];
    }

    /**
     * 获取退换货订单信息
     * @param int $ret_id
     * @return string
     */
    public function getOrderReturn($ret_id = 0)
    {
        $model = OrderReturn::where('ret_id', $ret_id);

        $model = $model->with([
            'orderInfo',
            'getReturnGoods'
        ]);

        $order_return = $model->first();

        $order_return = $order_return ? $order_return->toArray() : [];

        if (isset($order_return['order_info']) && !empty($order_return['order_info'])) {
            $order_return = collect($order_return)->merge($order_return['order_info'])->except('order_info')->all();
        }

        return $order_return;
    }

    /**
     * 查询订单分单信息
     * @param int $order_id
     * @param array $columns
     * @return array
     */
    public function getChildOrderInfo($order_id = 0, $columns = ['*'])
    {
        $order = OrderInfo::select($columns)->where('main_order_id', $order_id)->get();
        $order = $order ? $order->toArray() : [];

        return $order;
    }

    /**
     * 查询订单商品列表
     * @param int $order_id
     * @return array
     */
    public function getOrderGoods($order_id = 0)
    {
        $model = OrderGoods::where('order_id', $order_id);

        $model = $model->with([
            'goods' => function ($query) {
                $query = $query->select('goods_id', 'goods_weight as goodsweight', 'is_shipping');

            }
        ]);

        $order_goods = $model->get();

        $order_goods = $order_goods ? $order_goods->toArray() : [];

        return $order_goods;
    }

    /**
     * 插入订单表
     * @param array $order
     * @return int
     */
    public function createOrder($order = [])
    {
        if (empty($order)) {
            return 0;
        }

        /* 过滤表字段 */
        $new_order = $this->baseRepository->getArrayfilterTable($order, 'order_info');

        $count = OrderInfo::where('order_sn', $order['order_sn'])->count();

        if ($count <= 0) {
            $order_id = OrderInfo::insertGetId($new_order);

            return $order_id;
        }

        return 0;
    }

    /**
     * 获取主订单储值卡使用金额
     * @param int $order_id
     * @return array
     */
    public function orderValueCardRecord($order_id = 0)
    {
        $cartUseVal = ValueCardRecord::where('order_id', $order_id)->first();
        return $cartUseVal ? $cartUseVal->toArray() : [];
    }

    /**
     * 记录分单订单使用储值卡
     * @param array $cartValOther
     * @return array
     */
    public function createValueCardRecord($cartValOther = [])
    {
        if (empty($cartValOther)) {
            return [];
        }

        return ValueCardRecord::insert($cartValOther);
    }

    /**
     * 修改优惠券使用
     * @param int $user_id
     * @param int $order_id
     * @param int $new_order_id
     * @return mixed
     */
    public function updateCoupons($user_id = 0, $order_id = 0, $new_order_id = 0)
    {
        if (empty($new_order_id)) {
            return false;
        }

        return CouponsUser::where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->update(['order_id' => $new_order_id]);
    }

    /**
     * 插入订单商品
     * @param array $goods
     * @return int
     */
    public function createOrderGoods($goods = [])
    {
        if (empty($goods)) {
            return false;
        }

        unset($goods['rec_id']);
        unset($goods['get_goods']);

        /* 过滤表字段 */
        $goods = $this->baseRepository->getArrayfilterTable($goods, 'order_goods');

        $res = OrderGoods::insert($goods);

        return $res;
    }

    /**
     * 订单商品数量
     * @param array $condition
     * @return mixed
     */
    public function getOrderGoodCount($condition = [])
    {
        $model = OrderGoods::whereRaw(1);

        if (isset($condition['order_id']) && $condition['order_id'] > 0) {
            $model = $model->where('order_id', $condition['order_id']);
        }

        if (isset($condition['goods_id']) && $condition['goods_id'] > 0) {
            $model = $model->where('goods_id', $condition['goods_id']);
        }

        if (isset($condition['is_real']) && $condition['is_real'] == 1) {
            $model = $model->where('is_real', 1);
        }

        $count = $model->count();

        return $count;
    }

    /**
     * 将支付LOG插入数据表
     * @param integer $order_id 订单编号
     * @param float $amount 订单金额
     * @param integer $type 支付类型
     * @param integer $is_paid 是否已支付
     * @param int $membership_card_id 权益卡ID
     * @return  int
     */
    public function insert_pay_log($order_id = 0, $amount, $type = PAY_SURPLUS, $is_paid = 0, $membership_card_id = 0)
    {
        if ($order_id) {

            $pay_log = [
                'order_id' => $order_id,
                'order_amount' => $amount,
                'order_type' => $type,
                'is_paid' => $is_paid,
                'membership_card_id' => $membership_card_id
            ];

            return PayLog::insertGetId($pay_log);
        }

        return 0;
    }

    /**
     * 返回商家手机号
     * @param int $ruid
     * @return mixed
     */
    public function sms_shop_mobile($ruid = 0)
    {
        $sms_shop_mobile = SellerShopinfo::where('ru_id', $ruid)->value('mobile');

        return $sms_shop_mobile;
    }

    /**
     * 是否开启下单自动发短信、邮件
     * @return array
     */
    public function auto_sms()
    {
        $crons = Crons::where('cron_code', 'auto_sms')->where('enable', 1)->first();

        return $crons ? $crons->toArray() : [];
    }

    /**
     * 插入定时发送短信
     * @param array $data
     * @return bool
     */
    public function createAutoSms($data = [])
    {
        if (empty($data)) {
            return false;
        }

        return AutoSms::insert($data);
    }

    /**
     * 订单操作记录
     * @param int $order_id
     * @param int $order_status
     * @param int $shipping_status
     * @param int $pay_status
     * @param string $note
     * @param string $username
     * @param int $place
     * @param int $confirm_take_time
     */
    public function order_action($order_id, $order_status, $shipping_status, $pay_status, $note = '', $username = '', $place = 0, $confirm_take_time = 0)
    {
        $log_time = $confirm_take_time > 0 ? $confirm_take_time : $this->timeRepository->getGmTime();

        $other = [
            'order_id' => $order_id,
            'action_user' => $username,
            'order_status' => $order_status,
            'shipping_status' => $shipping_status,
            'pay_status' => $pay_status,
            'action_place' => $place,
            'action_note' => $note,
            'log_time' => $log_time
        ];
        OrderAction::insert($other);
    }

    /**
     * 退换货订单操作记录
     * @param string $ret_id 退换货编号
     * @param string $return_status 退货状态
     * @param string $refound_status 退款状态
     * @param string $note 备注
     * @param string $username 用户名，用户自己的操作则为 buyer
     * @param int $place
     * @param int $confirm_take_time
     * @return  void
     */
    public function return_action($ret_id, $return_status = '', $refound_status = '', $note = '', $username = '', $place = 0, $confirm_take_time = 0)
    {
        if ($ret_id) {
            $log_time = $confirm_take_time > 0 ? $confirm_take_time : $this->timeRepository->getGmTime();

            $other = [
                'ret_id' => $ret_id,
                'action_user' => $username,
                'return_status' => $return_status,
                'refound_status' => $refound_status,
                'action_place' => $place,
                'action_note' => $note,
                'log_time' => $log_time
            ];
            ReturnAction::insert($other);
        }
    }

    /**
     * 退款步骤 一
     * @param int $order_id
     * @param int $ret_id
     * @param array $refund_extend 扩展传参
     * @param array $order_info
     * @param array $order_return
     *
     * @return bool
     */
    public function refund($order_id = 0, $ret_id = 0, $refund_extend = [], $order_info = [], $order_return = [])
    {
        if (empty($order_info)) {
            $order_info = $this->getOrderInfo($order_id);
        }

        if (empty($order_return)) {
            $order_return = $this->getOrderReturn($ret_id);
        }

        // 初始化
        $refund_amount = $refund_extend['refund_amount'] ?? 0;
        $refund_shipping_fee = $refund_extend['refund_shipping_fee'] ?? 0;
        $refund_type = $refund_extend['refund_type'] ?? 1; // 退款类型
        $refund_note = $refund_extend['refund_note'] ?? ''; // 退款说明
        $return_time = $refund_extend['return_time'] ?? ''; // 退款时间

        // start
        $return_amount = $order_return['should_return'] ?? 0; // 应退款金额
        $order_shipping_fee = $order_info['shipping_fee'] ?? 0; // 订单运费

        // 一、处理退款（含是否退运费）
        if ($order_info['pay_status'] == PS_PAYED && $order_info['pay_status'] != PS_REFOUND) {

            // 退换货已退金额、已退运费
            $fee = $this->orderRefundRepository->orderReturnFee($order_id, $ret_id);

            // 判断商品退款是否大于实际商品退款金额
            $refound_fee = $fee['actual_return'] ?? 0;
            $paid_amount = $order_info['money_paid'] + $order_info['surplus'] - $refound_fee;
            if ($return_amount > $paid_amount) {
                $return_amount = $paid_amount - $order_shipping_fee;
            }

            // 判断运费退款是否大于实际运费退款金额
            $return_shipping_fee = $fee['return_shipping_fee'] ?? 0; // 退换货应退运费
            $refund_shipping_fee = 0;
            if ($return_shipping_fee > $order_shipping_fee) {
                $refund_shipping_fee = $order_shipping_fee - $return_shipping_fee;
            }

            // 最终退款总额
            $refund_amount = $return_amount + $refund_shipping_fee;

            // 二、 处理退款到帐

            // 在线原路退款  更新至1.1.8以后 可支持原路退款
            if ($refund_type == 6) {
                $refundOrder = [
                    'order_id' => $order_info['order_id'],
                    'pay_id' => $order_info['pay_id'],
                    'pay_status' => $order_info['pay_status'],
                    'referer' => $order_info['referer']
                ];
                $is_ok = $this->refoundService->refoundPay($refundOrder, $refund_amount);
            } else {
                // 退款至账户
                $is_ok = $this->order_refound($order_info, $refund_type, $refund_note, $refund_amount, 'refund');
            }

            if ($is_ok == false) {
                return false;
            }
        }

        // 三、处理退款后续 积分、红包、优惠券、储值卡等

        // 退款扩展参数
        $refund_extend['refund_amount'] = $refund_amount;
        $refund_extend['refund_shipping_fee'] = $refund_shipping_fee;
        $refund_extend['refund_type'] = $refund_type;
        $refund_extend['return_time'] = $return_time;
        $refund_extend['refund_note'] = $refund_note;

        $res = $this->order_refound_log($order_info, $order_return, $refund_extend);

        return $res;
    }

    /**
     * 退款步骤 二 处理退款到帐
     * @param array $order
     * @param int $refund_type 退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
     * @param string $refund_note 退款说明
     * @param int $refund_amount 退款金额（如果为0，取订单已付款金额）
     * @param string $operation
     * @return bool
     */
    public function order_refound($order = [], $refund_type = 3, $refund_note = '', $refund_amount = 0, $operation = '')
    {
        // 检查参数
        $user_id = $order['user_id'];
        if ($refund_amount <= 0) {
            return false;
        }
        if ($user_id == 0 && $refund_type == 1) {
            return false;
        }

        $in_operation = ['refound'];
        if (in_array($operation, $in_operation)) {
            $amount = $refund_amount;
        } else {
            $amount = $refund_amount > 0 ? $refund_amount : $order['should_return'];
        }

        // 订单 已支付且未退款
        if ($order['pay_status'] == PS_PAYED && $order['pay_status'] != PS_REFOUND) {

            /* 备注信息 */
            if ($refund_note) {
                $change_desc = $refund_note;
            } else {
                $change_desc = sprintf(trans('admin/order.order_refund'), $order['order_sn']);
            }

            if ($refund_type == 1) {
                // 如果是商家 退款至商家账户
                $is_ok = 1;
                if (isset($order['ru_id']) && $order['ru_id'] > 0 && $order['chargeoff_status'] == 2) {

                    $seller_shopinfo = $this->getShopInfo($order['ru_id']);

                    if ($seller_shopinfo) {
                        $seller_shopinfo['credit'] = $seller_shopinfo['seller_money'] + $seller_shopinfo['credit_money'];
                    }

                    if ($seller_shopinfo && $seller_shopinfo['credit'] > 0 && $seller_shopinfo['credit'] >= $amount) {

                        $user_money = (-1) * $amount;

                        // 商家帐户变动
                        $this->accountLogRepository->log_seller_account_change($order['ru_id'], $user_money);

                        // 商家帐户变动记录
                        $change_desc = "订单退款【" . $order['order_sn'] . "】" . $refund_note;
                        $change_type = 2;
                        $this->accountLogRepository->merchants_account_log($order['ru_id'], $user_money, 0, $change_desc, $change_type);

                    } else {
                        $is_ok = 0;
                    }
                }
                // 如果是会员 退款至会员账户
                if ($is_ok == 1) {
                    $this->accountLogRepository->log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
                    return true;
                } else {
                    /* 返回失败，不允许退款 */
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * 退款步骤 三 处理退款后续 积分、红包、优惠券、储值卡等
     * @param array $order_info
     * @param array $order_return
     * @param array $refund_extend 退款扩展参数
     * @return bool
     */
    public function order_refound_log($order_info = [], $order_return = [], $refund_extend = [])
    {
        // 退款 - 更新退换货 order_return
        $update_return = [
            'refound_status' => 1, // 已退款
            'return_status' => 4, //  完成退换货
            'agree_apply' => 1,
            'actual_return' => $refund_extend['refund_amount'] ?? 0,
            'return_shipping_fee' => $refund_extend['refund_shipping_fee'] ?? 0,
            'refund_type' => $refund_extend['refund_type'] ?? 6,
            'return_time' => $refund_extend['return_time'] ?? $this->timeRepository->getGmTime()
        ];
        OrderReturn::where('ret_id', $order_return['ret_id'])->update($update_return);

        // 退款 - 更新订单 order_info
        $order_info['goods']['total_goods_number'] = 0;
        // 订单商品
        if (isset($order_info['goods']) && !empty($order_info['goods'])) {
            foreach ($order_info['goods'] as $k => $v) {
                $goods_number = $v['goods_number'] ?? 0;
                $order_info['goods']['total_goods_number'] += $goods_number;
            }
        }

        // 如果退款数量小于订单商品数量
        $return_number = $order_return['get_return_goods']['return_number'] ?? 0;
        if ($order_info['goods']['total_goods_number'] > 0 && $order_info['goods']['total_goods_number'] > $return_number) {
            // 单品退货
            $update_order = [
                'order_status' => OS_RETURNED_PART,
            ];
        } else {
            // 整单退货
            $update_order = [
                'order_status' => OS_RETURNED,
                'pay_status' => PS_REFOUND,
                'shipping_status' => SS_UNSHIPPED,
                'money_paid' => 0,
                'invoice_no' => '',
                'order_amount' => 0
            ];
        }
        OrderInfo::where('order_id', $order_info['order_id'])->update($update_order);

        // 退款 - 更新商品销量
        $this->orderRefundRepository->updateGoodsSale($order_return['goods_id'], $return_number);

        // 退款 - 更新账单
        $this->orderRefundRepository->updateSellerBillOrder($order_info['order_id'], $refund_extend['refund_amount'], $refund_extend['refund_shipping_fee']);

        $refund_surplus = $order_info['surplus'] ?? 0; // 余额
        $refund_pay_points = $order_info['integral'] ?? 0; // 积分数量
        $refund_bonus_id = $order_info['bonus_id'] ?? 0; // 红包
        $refund_coupons = $order_info['coupons'] ?? 0; // 优惠券

        // 更新订单参数
        $update = [];

        // 退款 - 如果有 退还余额
        if ($refund_surplus > 0) {
            $surplus = $order_info['money_paid'] < 0 ? $refund_surplus + $order_info['money_paid'] : $refund_surplus;
            $change_desc = trans('admin/order.return_order_surplus') . $order_info['order_sn'];
            $this->accountLogRepository->log_account_change($order_info['user_id'], $surplus, 0, 0, 0, $change_desc);
            // 更新订单参数
            $update['order_amount'] = 0;
            $update['surplus'] = 0;
        }

        // 退款 - 如果有 退还红包
        if ($refund_bonus_id > 0) {
            $this->orderRefundRepository->return_bonus($order_info['order_id']);
            // 更新订单参数
            $update['bonus_id'] = 0;
            $update['bonus'] = 0;
        }

        // 退款 - 如果有 退还优惠券
        if ($refund_coupons > 0) {
            $this->orderRefundRepository->return_coupons($order_info['order_id']);
            // 更新订单参数
            $update['coupons'] = 0;
        }

        // 退款 - 如果有 退还储值卡
        $this->orderRefundRepository->return_card_money($order_info['order_id']);

        // 退款 - 如果有 退还积分
        if ($refund_pay_points > 0) {
            $change_desc = trans('admin/order.order_return_prompt') . $order_info['order_sn'] . " 购买的积分";
            $this->accountLogRepository->log_account_change($order_info['user_id'], 0, 0, 0, $refund_pay_points, $change_desc);
            // 更新订单参数
            $update['integral'] = 0;
            $update['integral_money'] = 0;
        }

        if ($update) {
            OrderInfo::where('order_id', $order_info['order_id'])->update($update);
        }

        // 退款 - 如果开启使用库存 退回库存
        if ($this->configService->getConfig('use_storage') == '1') {
            $stock_dec_time = $this->configService->getConfig('stock_dec_time');
            load_helper(['order']);
            if ($stock_dec_time == SDT_SHIP) {
                change_order_goods_storage($order_info['order_id'], false, SDT_SHIP, 6, 1, 0);
            } elseif ($stock_dec_time == SDT_PLACE) {
                change_order_goods_storage($order_info['order_id'], false, SDT_PLACE, 6, 1, 0);
            } elseif ($stock_dec_time == SDT_PAID) {
                change_order_goods_storage($order_info['order_id'], false, SDT_PAID, 6, 1, 0);
            }
        }

        return true;
    }

    /**
     * 获得店铺设置基本信息
     *
     * @access  public
     * @param int $seller_id
     * @return  array
     */
    public function getShopInfo($seller_id = 0, $type = 0)
    {
        $row = SellerShopinfo::where('ru_id', $seller_id);

        if ($type > 0) {
            if ($type == 1) {
                $with = 'getSellerQrcode';
            } elseif ($type == 2) {
                $with = 'getMerchantsStepsFields';
            } elseif ($type == 3) {
                $with = 'getSellerQrcode,getMerchantsStepsFields';
            }

            $with = explode(",", $with);
            $row = $row->with($with);
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        $row = $row && isset($row['get_seller_qrcode']) && $row['get_seller_qrcode'] ? array_merge($row, $row['get_seller_qrcode']) : $row;
        $row = $row && isset($row['get_merchants_steps_fields']) && $row['get_merchants_steps_fields'] ? array_merge($row, $row['get_merchants_steps_fields']) : $row;

        return $row;
    }

    /**
     * 订单是否有分销商品
     * @param int $order_id
     * @return mixed
     */
    public function is_drp_order($order_id = 0)
    {
        if (empty($order_id)) {
            return 0;
        }

        $count = OrderGoods::where('order_id', $order_id)->where('is_distribution', 1)->where('drp_money', '>', 0)->count();

        return $count;
    }

    /**
     * 商家是否自营
     * @param int $user_id 商家id
     * @return int
     */
    public function is_self_run($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $result = MerchantsShopInformation::where('user_id', $user_id)->value('self_run');

        return $result;
    }

    /**
     * 子订单数量与列表
     * @param int $order_id
     * @param array $columns
     * @return array
     */
    public function getChildOrderList($order_id = 0, $columns = ['*'])
    {
        $model = OrderInfo::select($columns)->where('main_order_id', $order_id);

        $total = $model->count();

        $list = $model->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 格式化订单商品
     * @param array $order_goods
     * @param string $order_sn
     * @return array
     */
    public function trans_order_goods($order_goods = [], $order_sn = '')
    {
        if (empty($order_goods)) {
            return [];
        }

        $goods = [];
        foreach ($order_goods as $row) {

            if (isset($row['get_goods'])) {
                $row['goods_thumb'] = $row['get_goods']['goods_thumb'] ?? '';
                $row['goods_img'] = $row['get_goods']['goods_img'] ?? '';
            }

            $row['goods_price_format'] = $this->dscRepository->getPriceFormat($row['goods_price'] + $row['shipping_fee']);

            //图片显示
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);

            $row['formated_subtotal'] = $this->dscRepository->getPriceFormat($row['goods_price'] * $row['goods_number']);
            $row['formated_goods_price'] = $this->dscRepository->getPriceFormat($row['goods_price']);

            $goods_attr[] = empty($row['goods_attr']) ? '' : explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

            $goods_extend = app(GoodsRepository::class)->goods_extend($row['goods_id']);
            if ($row['is_reality'] == -1 && $goods_extend) {
                $row['is_reality'] = $goods_extend['is_reality'];
            }
            if ($goods_extend['is_return'] == -1 && $goods_extend) {
                $row['is_return'] = $goods_extend['is_return'];
            }
            if ($goods_extend['is_fast'] == -1 && $goods_extend) {
                $row['is_fast'] = $goods_extend['is_fast'];
            }

            // 获得退货表数据
            $row['ret_id'] = app(OrderRefundRepository::class)->order_return_ret($row['rec_id']);

            $row['back_order'] = []; //return_order_info($row['ret_id']);

            // 商品快照
            $trade_id = app(GoodsRepository::class)->find_snapshot($order_sn, $row['goods_id']);
            if ($trade_id) {
                $row['trade_url'] = url('/') . "/trade_snapshot.php?act=trade&tradeId=" . $trade_id . "&snapshot=true";
            }

            $row['url'] = url('goods.php?goods_id' . $row['goods_id']);

            $goods[] = $row;
        }

        return $goods;
    }

    /**
     * 订单账单
     * @param int $order_id
     * @param array $where
     * @return array
     */
    public function get_seller_bill_order($order_id = 0, $where = [])
    {
        $model = SellerBillOrder::where('order_id', $order_id);

        if (!empty($where)) {
            if (isset($where['chargeoff_status']) && $where['chargeoff_status'] == true) {
                $model = $model->where('chargeoff_status', '>', 0);
            }
        }

        $model = $model->with([
            'sellerCommissionBill' => function ($query) {
                $query->select('id', 'bill_sn', 'seller_id', 'proportion', 'commission_model');
            }
        ]);

        $model = $model->first();

        $bill = $model ? $model->toArray() : [];

        return $bill;
    }

    /**
     * 更新订单结算状态
     * @param int $order_id
     * @param int $chargeoff_status
     * @return bool
     */
    public function update_order_chargeoff_status($order_id = 0, $chargeoff_status = 0)
    {
        if (empty($order_id)) {
            return false;
        }

        return OrderInfo::where('order_id', $order_id)->update(['chargeoff_status' => $chargeoff_status]);
    }

    /**
     * 更新订单
     * @param int $order_id 订单id
     * @param array $order key => value
     * @return  bool
     */
    public function update_order($order_id = 0, $order = [])
    {
        if (empty($order_id) || empty($order)) {
            return false;
        }
        /* 获取表字段 */
        $order_other = $this->baseRepository->getArrayfilterTable($order, 'order_info');
        return OrderInfo::where('order_id', $order_id)->update($order_other);
    }

    /**
     * 更新用户订单
     * @param int $order_id 订单id
     * @param int $user_id 会员id
     * @param array $data key => value
     * @return  bool
     */
    public function update_user_order($order_id = 0, $user_id = 0, $data = [])
    {
        if (empty($order_id) || empty($data)) {
            return false;
        }

        $order_other = $this->baseRepository->getArrayfilterTable($data, 'order_info');

        return OrderInfo::where('order_id', $order_id)->where('user_id', $user_id)->update($order_other);
    }
}
