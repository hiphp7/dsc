<?php

namespace App\Services\Payment;

use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\PayLog;
use App\Models\Payment;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use Illuminate\Support\Str;

/**
 * Class PaymentService
 * @package App\Services\Payment
 */
class PaymentService
{
    protected $baseRepository;
    protected $commonRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获取支付信息
     * @param array $where
     * @return array
     */
    public function getPaymentInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $res = Payment::whereRaw(1);

        if (isset($where['pay_id'])) {
            $res = $res->where('pay_id', $where['pay_id']);
        }

        if (isset($where['pay_name'])) {
            $res = $res->where('pay_name', $where['pay_name']);
        }

        if (isset($where['pay_code'])) {
            $res = $res->where('pay_code', $where['pay_code']);
        }

        if (isset($where['enabled'])) {
            $res = $res->where('enabled', $where['enabled']);
        }

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 订单确认页可选支付列表
     *
     * @param int $order_id
     * @param int $support_cod
     * @param int $cod_fee
     * @param int $is_online
     * @return array
     */
    public function getPaymentList($order_id = 0, $support_cod = 0, $cod_fee = 0, $is_online = 0)
    {
        $list = $this->availablePaymentList($support_cod, $cod_fee, $is_online);

        if (empty($list)) {
            return [];
        }

        $pay_id = OrderInfo::where('order_id', $order_id)->value('pay_id');

        foreach ($list as $k => $val) {
            if (isset($pay_id) && $val['pay_id'] == $pay_id) {
                $list[$k]['selected'] = true;
            }
        }

        return $list;
    }

    /**
     * 取得可用的支付方式列表
     *
     * @param int $support_cod 配送方式是否支持货到付款
     * @param int $cod_fee 货到付款手续费（当配送方式支持货到付款时才传此参数）
     * @param int $is_online 是否支持在线支付
     * @param int $is_balance 是否支持余额支付
     * @return array
     * @throws \Exception
     */
    public function availablePaymentList($support_cod = 0, $cod_fee = 0, $is_online = 0, $is_balance = 0)
    {
        $res = Payment::select('pay_id', 'pay_code', 'pay_name', 'pay_fee', 'is_online', 'pay_desc', 'pay_config', 'is_cod')
            ->where('enabled', 1);

        if ($support_cod == 0) {
            $res->where('is_cod', 0); // 如果不支持货到付款
        }
        if ($is_online == 1) {
            $res->where('is_online', 1); // 在线支付
        }
        $res = $res->orderBy('pay_order', 'ASC')
            ->get();
        $payment_list = $res ? $res->toArray() : [];

        if (empty($payment_list)) {
            return ['code' => 1, 'msg' => lang('payment.pay_not_install')];
        }

        foreach ($payment_list as $key => $payment) {
            if ($payment['is_cod'] == '1') {
                $payment['pay_fee'] = $cod_fee;
            }

            $payment['format_pay_fee'] = strpos($payment['pay_fee'], '%') !== false ? $payment['pay_fee'] : $this->dscRepository->getPriceFormat($payment['pay_fee'], false);

            //pc端去除ecjia的支付方式
            if (substr($payment['pay_code'], 0, 4) == 'pay_') {
                unset($payment_list[$key]);
                continue;
            }

            $plugins = plugin_path('Payment/' . Str::studly($payment['pay_code']) . '/' . Str::studly($payment['pay_code']) . '.php');
            if (!file_exists($plugins)) {
                unset($payment_list[$key]);
            }
            // 白条支付
            if ($payment['pay_code'] == 'chunsejinrong') {
                unset($payment_list[$key]);
                continue;
            }
            // 收银台 不显示 在线支付, 仅显示 微信或支付宝等
            if ($is_online == 1 && $payment['is_online'] != 1) {
                unset($payment_list[$key]);
            }
            // 结算页 显示在线支付
            if ($is_online == 0 && $payment['is_online'] == 1) {
                unset($payment_list[$key]);
            }
            // 手机端 是否支持余额支付
            if ($payment['pay_code'] == 'balance' && $is_balance == 0) {
                unset($payment_list[$key]);
            }
            // 手机端 不显示邮局汇款、银行汇款
            if ($payment['pay_code'] == 'post' || $payment['pay_code'] == 'bank') {
                unset($payment_list[$key]);
            }
            if ($payment['pay_code'] == 'wxpay') {
                if (!file_exists(MOBILE_WECHAT)) {
                    unset($payment_list[$key]);
                }
                // 非微信浏览控制显示h5
                if (is_wechat_browser() == false && $this->is_wxh5() == 0) {
                    unset($payment_list[$key]);
                }
            }
        }

        $payment_list = collect($payment_list)->values()->all();

        return $payment_list;
    }

    /**
     * 取得支付按钮
     *
     * @param int $user_id
     * @param int $pay_id
     * @param array $order
     * @return array
     */
    public function getpaymentCode($user_id = 0, $pay_id = 0, $order = [])
    {
        if (empty($user_id) || empty($pay_id)) {
            return ['code' => 1, 'msg' => lang('common.illegal_operate')];
        } else {
            $payment_info = $this->getpaymentInfo(['pay_id' => $pay_id, 'enabled' => 1]);

            $order['pay_desc'] = $payment_info['pay_desc'];

            $payment = $this->getPayment($payment_info['pay_code']);

            if ($payment === false) {
                return ['code' => 1, 'msg' => lang('common.illegal_operate')];
            }

            return $payment->get_code($order, unserialize_config($payment_info['pay_config']), $user_id);
        }
    }

    /**
     * 支付同步回调通知
     *
     * @param array $info
     * @param string $code
     * @return array
     */
    public function getCallback($info = [], $code = '')
    {
        $log_id = isset($info['log_id']) ? intval($info['log_id']) : 0;

        $payment = $this->getPayment($code);
        // 提示类型
        if ($payment === false) {
            $result = [
                'msg' => lang('payment.pay_disabled'),
                'msg_type' => 2,
            ];
        } else {
            if ($code == 'alipay') {
                $log_id = $payment->parse_trade_no($info['out_trade_no']);
            }

            // 微信h5中间页面
            if (isset($info['type']) && $info['code'] == 'wxpay' && $info['type'] == 'wxh5') {
                $result = $this->Wxh5($code, $log_id);
                // 跳转至h5中间页面
                return $result;
            }

            if ($payment->callback()) {
                $result = [
                    'msg' => lang('payment.pay_success'),
                    'msg_type' => 0,
                ];
            } else {
                $result = [
                    'msg' => lang('payment.pay_fail'),
                    'msg_type' => 1,
                ];
            }

            // 根据不同订单类型（普通、充值） 跳转
            if (isset($log_id) && !empty($log_id)) {
                $pay_log = PayLog::query()->select('order_type', 'order_id')->where('log_id', $log_id)->orderBy('log_id', 'DESC')->first();
                $pay_log = $pay_log ? $pay_log->toArray() : [];

                if (empty($pay_log)) {
                    return ['msg' => lang('payment.pay_fail'), 'msg_type' => 1, 'url' => url('mobile/#/')];
                }

                // 订单类支付
                if ($pay_log['order_type'] == PAY_ORDER) {

                    $order = OrderInfo::query()->select('order_id', 'extension_code', 'team_id', 'is_zc_order', 'zc_goods_id')->where('order_id', $pay_log['order_id'])->first();
                    $order = $order ? $order->toArray() : [];

                    if (empty($order)) {
                        return ['msg' => lang('payment.pay_fail'), 'msg_type' => 1, 'url' => url('mobile/#/')];
                    }

                    $result['extension_code'] = $order['extension_code'] ?? '';
                    if ($order['extension_code'] == 'team_buy') {
                        // 拼团
                        $result['extension_code'] = $order['extension_code'];
                        $result['url'] = dsc_url('/#/team/wait') . '?' . http_build_query(['team_id' => $order['team_id'], 'status' => 1], '', '&');
                    } elseif ($order['is_zc_order'] == 1 && $order['zc_goods_id'] > 0) {
                        // 众筹
                        $result['extension_code'] = 'crowd_buy';
                        $result['url'] = dsc_url('/#/crowdfunding/order');
                    } else {
                        // 普通订单
                        $result['url'] = dsc_url('/#/user/orderDetail/' . $pay_log['order_id']);
                    }

                } elseif ($pay_log['order_type'] == PAY_WHOLESALE) {
                    // 供求订单支付
                    $result['url'] = dsc_url('/#/supplier/orderlist');
                } else {
                    // 无订单类支付
                    if ($pay_log['order_type'] == PAY_REGISTERED) {
                        // 分销购买
                        $result['extension_code'] = 'drp_buy';
                        $result['url'] = dsc_url('/#/drp/drpinfo');
                    } elseif ($pay_log['order_type'] == PAY_SURPLUS) {
                        // 会员充值
                        $result['url'] = dsc_url('/#/user/account');
                    }
                }

                return $result;
            }
        }

        return $result;
    }

    /**
     * 获得支付实例
     *
     * @param $code
     * @return bool|\Illuminate\Foundation\Application|mixed
     */
    public function getPayment($code)
    {
        /* 判断启用状态 */
        $condition = [
            'pay_code' => $code,
            'enabled' => 1
        ];
        $enabled = Payment::where($condition)->count();

        $plugin = false;
        if ($code && strpos($code, 'pay_') === false) {
            $plugin = $this->commonRepository->paymentInstance($code);
            if (is_null($plugin) || $enabled == 0) {
                return false;
            }
        }

        /* 实例化插件 */
        return $plugin;
    }

    /**
     * 微信支付h5同步通知中间页面
     *
     * @param string $code
     * @param int $log_id
     * @return array
     */
    public function Wxh5($code = '', $log_id = 0)
    {
        // 显示页面
        if (!empty($log_id)) {
            $log_id = intval($log_id);
            $pay_log = PayLog::query()->select('order_type', 'order_id', 'is_paid')->where('log_id', $log_id)->orderBy('log_id', 'DESC')->first();
            $pay_log = $pay_log ? $pay_log->toArray() : [];

            // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == PAY_ORDER) {
                $order_url = dsc_url('/#/user/orderDetail') . '/' . $pay_log['order_id'];
            } elseif ($pay_log['order_type'] == PAY_SURPLUS) {
                $order_url = dsc_url('/#/user/account');
            } elseif ($pay_log['order_type'] == PAY_REGISTERED) {
                //分销购买
                $order_url = dsc_url('/#/drp');
            } elseif ($pay_log['order_type'] == PAY_TOPUP) {
                //拼团
                $order_url = dsc_url('/#/team/order');
            } elseif ($pay_log['order_type'] == PAY_WHOLESALE) {
                //供求
                $result['url'] = dsc_url('/#/supplier/orderlist');
            } else {
                $order_url = dsc_url('/#/user/order');
            }
            // 支付状态
            $args = [
                'code' => $code,
                'status' => $pay_log['is_paid'] ?? 0,
                'log_id' => $log_id
            ];
            $respond_url = dsc_url('/#/respond?' . http_build_query($args, '', '&'));
        } else {
            $args = [
                'code' => $code,
                'status' => 0
            ];
            $respond_url = dsc_url('/#/respond?' . http_build_query($args, '', '&'));
        }

        $is_wxh5 = ($code == 'wxpay' && !is_wechat_browser()) ? 1 : 0;

        $result = [
            'is_wxh5' => $is_wxh5,
            'repond_url' => $respond_url,
            'order_url' => $order_url ?? ''
        ];
        return $result;
    }

    /**
     * 支付异步回调通知
     *
     * @param string $code
     * @param string $refer
     * @param string $type
     * @return array
     */
    public function getNotify($code, $refer = '', $type = '')
    {
        $payment = $this->getPayment($code);
        if ($payment === false) {
            return [];
        }

        return $payment->notify($refer, $type);
    }

    /**
     * 退款异步回调通知
     *
     * @param string $code
     * @param string $refer
     * @param string $type
     * @return array
     */
    public function getNotifyRefound($code, $refer = '', $type = '')
    {
        $payment = $this->getPayment($code);
        if ($payment === false) {
            return [];
        }

        return $payment->notify_refound($refer, $type);
    }

    /**
     * 立即支付
     *
     * @param $info
     * @return array
     */
    public function onlinepay($info)
    {
        $order_sn = $info['order_sn'];
        $user_id = $info['user_id'];

        $order_id = OrderInfo::select('order_id')->where('order_sn', $order_sn)->where('user_id', $user_id)->first();

        $order_id = $order_id ? $order_id->toArray() : [];
        $order_id = $order_id['order_id'];

        if (empty($order_id)) {
            return ['code' => 1, 'msg' => lang('common.illegal_operate')];
        }
        // 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
        $payment_list = $this->availablePaymentList($support_cod = 1, $cod_fee = 0, $is_online = 1);

        if (empty($payment_list)) {
            return ['code' => 1, 'msg' => lang('payment.please_install_pay')];
        }
        /* 计算订单各种费用之和的语句 */
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
        $order_id = intval($order_id);
        if ($order_id > 0) {
            //@模板堂-bylu 这里连表查下支付方法表,获取到"pay_code"字段值;

            $order = OrderInfo::selectRaw("*, $total_fee")->where('order_id', $order_id);
        } else {
            //@模板堂-bylu 这里连表查下支付方法表,获取到"pay_code"字段值;
            $order = OrderInfo::selectRaw("*, $total_fee")->where('order_sn', $order_sn);
        }

        $order = $order->first();
        /* 订单详情 */
        $order = $order ? $order->toArray() : [];
        //获取log_id
        $log_id = PayLog::where('order_id', $order_id)->where('order_type', PAY_ORDER)->orderBy('log_id', 'DESC')->value('log_id');

        $order['log_id'] = $log_id ?? 0;

        /* 取得支付信息，生成支付代码 */
        if ($order['order_amount'] > 0) {
            //查询"在线支付"的pay_id;
            $onlinepay_pay = Payment::select('pay_id')->where('pay_code', 'onlinepay')->first();
            $onlinepay_pay = $onlinepay_pay ? $onlinepay_pay->toArray() : [];

            $onlinepay_pay_id = $onlinepay_pay['pay_id'] ?? 0;

            $enabled = Payment::where('pay_id', $order['pay_id'])->value('enabled');
            $enabled = $enabled ? $enabled : 0;

            if ($enabled == 0 || $order['pay_id'] == $onlinepay_pay_id) {
                $default_payment = reset($payment_list);
                $order['pay_id'] = $default_payment['pay_id'];
            }
        } else {
            return ['code' => 1, 'msg' => lang('common.illegal_operate')];
        }
        //默认是支付宝
        if (!empty($order['pay_id'])) {
            $payment_info = $this->getPaymentInfo(['pay_id' => $order['pay_id'], 'enabled' => 1]);

            //改变订单的支付名称和支付id
            $payData = [
                'pay_id' => $payment_info['pay_id'],
                'pay_name' => $payment_info['pay_name'],
            ];
            OrderInfo::where('order_id', $order['order_id'])->update($payData);

            $child_order_id_arr = OrderInfo::select('order_id')->where('main_order_id', $order['order_id'])->get();
            $child_order_id_arr = $child_order_id_arr ? $child_order_id_arr->toArray() : [];

            if ($order['main_order_id'] == 0 && count($child_order_id_arr) > 0 && $order['order_id'] > 0) {
                $data = [
                    'pay_id' => $order['pay_id'],
                    'pay_name' => $payment_info['pay_name'],
                ];
                OrderInfo::where('main_order_id', $order['order_id'])->update($data);
            }
        } else {
            return ['code' => 1, 'msg' => lang('common.illegal_operate')];
        }

        $order['pay_desc'] = $payment_info['pay_desc'];

        $payment = $this->getPayment($payment_info['pay_code']);

        if ($payment === false) {
            return ['code' => 1, 'msg' => lang('common.illegal_operate')];
        }
        return $payment->get_code($order, unserialize_config($payment_info['pay_config']), $user_id);
    }

    /**
     * 切换支付方式
     *
     * @param int $order_id
     * @param int $pay_id
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function change_payment($order_id = 0, $pay_id = 0, $user_id = 0)
    {
        if (empty($order_id)) {
            return ['code' => 1, 'msg' => lang('common.illegal_operate')];
        }

        /* 订单详情 */
        /* 计算订单各种费用之和的语句 */
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
        $order_id = intval($order_id);

        //@模板堂-bylu 这里连表查下支付方法表,获取到"pay_code"字段值;
        $order = OrderInfo::selectRaw("*, $total_fee")->where('order_id', $order_id);
        $order = $order->first();

        if (empty($order)) {
            return [];
        }

        /* 订单详情 */
        $order = $order ? $order->toArray() : [];
        //获取log_id
        $log_id = PayLog::where('order_id', $order_id)->where('order_type', PAY_ORDER)->orderBy('log_id', 'DESC')->value('log_id');

        $order['log_id'] = $log_id ?? 0;

        $payment_info = $this->getPaymentInfo(['pay_id' => $pay_id, 'enabled' => 1]);

        //改变订单的支付名称和支付id
        $payData = [
            'pay_id' => $payment_info['pay_id'],
            'pay_name' => $payment_info['pay_name'],
        ];

        OrderInfo::where('order_id', $order_id)->update($payData);

        //是否有子订单
        $child_order_id_arr = OrderInfo::select('order_id')->where('main_order_id', $order_id)->get();
        $child_order_id_arr = $child_order_id_arr ? $child_order_id_arr->toArray() : [];
        if ($order['main_order_id'] == 0 && count($child_order_id_arr) > 0 && $order_id > 0) {
            OrderInfo::where('main_order_id', $order['order_id'])->update($payData);
        }

        $order['pay_desc'] = $payment_info['pay_desc'];

        $payment = $this->getPayment($payment_info['pay_code']);

        if ($payment === false) {
            return [];
        }

        // 指定支付类型
        if (request()->has('type')) {
            $order['type'] = request()->get('type', '');
        }

        return $payment->get_code($order, unserialize_config($payment_info['pay_config']), $user_id);
    }

    /**
     * 是否开通微信h5配置
     * @return int
     */
    protected function is_wxh5()
    {
        $rs = Payment::where(['pay_code' => 'wxpay'])->value('pay_config');
        $config = [];
        if (!empty($rs)) {
            $rs = unserialize($rs);
            foreach ($rs as $key => $value) {
                $config[$value['name']] = $value['value'];
            }
        }

        return (isset($config) && isset($config['is_h5'])) ? $config['is_h5'] : 0;
    }

    /**
     * 记录订单交易信息
     *
     * @param int $log_id
     * @param array $data
     */
    public function updatePayLog($log_id = 0, $data = [])
    {
        if ($log_id > 0 && $data) {
            // 数组转json
            $pay_trade_data = is_array($data) ? json_encode($data, JSON_UNESCAPED_SLASHES) : $data;

            $model = PayLog::where('log_id', $log_id);

            $model = $model->first();
            if ($model) {
                $model->pay_trade_data = $pay_trade_data;
                $model->save();
            }
        }
    }

    /**
     * 记录退款交易信息
     *
     * @param string $return_sn
     * @param string $order_sn
     * @param array $data
     */
    public function updateReturnLog($return_sn = '', $order_sn = '', $data = [])
    {
        if ($data) {
            // 数组转json
            $return_trade_data = is_array($data) ? json_encode($data, JSON_UNESCAPED_SLASHES) : $data;

            if ($order_sn) {
                $model = OrderReturn::where('order_sn', $order_sn)->first();
            } else {
                $model = OrderReturn::where('return_sn', $return_sn)->first();
            }

            if ($model) {
                $model->return_trade_data = $return_trade_data;
                $model->save();
            }
        }
    }

    /**
     * 优化切换支付方式后支付 异步更新订单支付方式
     * @param string $order_sn
     * @param string $pay_code
     * @param array $payment
     * @return bool
     */
    public function updateOrderPayment($order_sn = '', $pay_code = '', $payment = [])
    {
        if (!empty($order_sn)) {
            // 通过pay_code查
            if (!empty($pay_code)) {
                $payment = Payment::where('pay_code', $pay_code)->select('pay_id', 'pay_name')->first();
                $payment = $payment ? $payment->toArray() : [];
            }

            $orderModel = OrderInfo::where('order_sn', $order_sn)->first();

            if ($orderModel) {
                // 白条支付 用支付宝或微信支付还款 不更新支付方式
                $order_pay_code = Payment::where('pay_id', $orderModel->pay_id)->value('pay_code');
                if ($order_pay_code == 'chunsejinrong') {
                    return false;
                }

                $orderModel->pay_id = $payment['pay_id'];
                $orderModel->pay_name = $payment['pay_name'];
                $orderModel->save;
            }
        }
    }

    /**
     * 微信支付二维码
     * @param string $code_url
     * @return bool|string
     */
    public function wxpayQrcode($code_url = '')
    {
        if (empty($code_url)) {
            return false;
        }

        $img_src = route('qrcode', ['code_url' => $code_url, 't' => time()]);

        $qrcode_right = asset('themes/ecmoban_dsc2017/images/weixin-qrcode.jpg');
        $sj = asset('themes/ecmoban_dsc2017/images/sj.png');

        $wxpay_lbi = '<div id="wxpay_dialog" class="hide">' .
            '<div class="modal-box">' .
            '<div class="modal-left">' .
            '<p><span>请使用 </span><span class="orange">微信 </span><i class="icon icon-qrcode"></i><span class="orange"> 扫一扫</span><br>扫描二维码支付</p>' .
            '<div class="modal-qr">' .
            '<div class="modal-qrcode"><img src="' . $img_src . '" /></div>' .
            '<div class="model-info"><img src="' . $sj . '" class="icon-clock" /><span>二维码有效时长为2小时, 请尽快支付</span></div>' .
            '</div>' .
            '</div>' .
            '<div class="modal-right"><img src="' . $qrcode_right . '" /></div>' .
            '</div>' .
            '</div>';

        return $wxpay_lbi;
    }

    /**
     * 取得支付方式id列表
     * @param bool $is_cod 是否货到付款
     * @return  array
     */
    public function paymentIdList($is_cod)
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
     * 支付日志关联查询未支付、有效订单信息 （用于支付订单查询）
     * @param int $log_id
     * @return array
     */
    public function getUnPayOrder($log_id = 0)
    {
        if (empty($log_id)) {
            return [];
        }

        $model = PayLog::query()->where('log_id', $log_id);

        // 订单状态 未确认，已确认、已分单、未支付
        $model = $model->with([
            'orderInfo' => function ($query) {
                $query->select('order_id', 'order_sn', 'referer')->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED, OS_SPLITED])->whereIn('pay_status', [PS_PAYING, PS_UNPAYED]);
            }
        ]);

        $model = $model->orderBy('log_id', 'DESC')
            ->first();

        $payLog = $model ? $model->toArray() : [];

        if ($payLog) {
            if ($payLog['order_type'] == PAY_ORDER) {
                if (isset($payLog['order_info']) && !empty($payLog['order_info'])) {
                    $payLog = collect($payLog)->merge($payLog['order_info'])->except('order_info')->all();
                }
            }
        }

        return $payLog;
    }

    /**
     * 支付日志关联查询已支付、有效订单信息 （用于支付退款申请）
     * @param int $order_id
     * @return array
     */
    public function getPaidOrder($order_id = 0)
    {
        if (empty($order_id)) {
            return [];
        }

        $model = PayLog::query()->where('order_id', $order_id)->where('order_type', PAY_ORDER);

        // 订单状态 已确认、已分单、已支付、未退款
        $model = $model->with([
            'orderInfo' => function ($query) {
                $query->select('order_id', 'order_sn', 'referer')->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])->where('pay_status', PS_PAYED);
            }
        ]);

        $model = $model->orderBy('log_id', 'DESC')
            ->first();

        $payLog = $model ? $model->toArray() : [];

        if ($payLog) {
            if (isset($payLog['order_info']) && !empty($payLog['order_info'])) {
                $payLog = collect($payLog)->merge($payLog['order_info'])->except('order_info')->all();
            }
        }

        return $payLog;
    }
}
