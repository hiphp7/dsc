<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\QrpayLog;
use App\Notifications\Qrpay\QrpayOrderPaidNotify;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Qrpay\QrpayService;
use App\Services\Wechat\WechatUserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Payment\Client\Notify;
use Payment\Client\Query;
use Payment\Common\PayException;
use Payment\Config;

/**
 * Class QrpayController
 * @package App\Api\Fourth\Controllers
 */
class QrpayController extends Controller
{
    protected $pay_code = '';

    protected $qrpayService;
    protected $wechatUserService;
    protected $merchantCommonService;

    public function __construct(
        QrpayService $qrpayService,
        WechatUserService $wechatUserService,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->qrpayService = $qrpayService;
        $this->wechatUserService = $wechatUserService;
        $this->merchantCommonService = $merchantCommonService;

        $this->middleware('enable_cross'); // 跨域中间件

        // 判断是支付宝或微信
        $this->pay_code = (is_wechat_browser() == true) ? 'wxpay' : 'alipay';
    }

    /**
     *  首页 显示商家和二维码信息
     *
     * @post api/qrpay?id=1
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $qrpay_id = $request->input('id', 0);

        $qrpay_info = $this->qrpayService->get_qrpay_info($qrpay_id);

        if (empty($qrpay_info)) {
            return $this->setStatusCode(404)->failed(lang('wechat.qrpay_not_exist'));
        }

        // 收款码类型 指定金额
        if ($qrpay_info['type'] == 1) {
            $pay_amount = $qrpay_info['amount'];
        } else {
            $pay_amount = 0;
        }

        $shop_name = $this->merchantCommonService->getShopName($qrpay_info['ru_id'], 1);

        $detail = [
            'seller' => $shop_name,
            'qrcode' => [
                'type' => $qrpay_info['type'],
                'amount' => $pay_amount,
                'qrpay_name' => $qrpay_info['qrpay_name'],
            ]
        ];

        return $this->succeed($detail);
    }

    /**
     * 发起支付
     * @post api/qrpay/pay?id=1
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function pay(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $qrpay_id = $request->input('id', 0);

        $qrpay_info = $this->qrpayService->get_qrpay_info($qrpay_id);

        if (empty($qrpay_info)) {
            return $this->setStatusCode(404)->failed(lang('wechat.qrpay_not_exist'));
        }

        // 收款码类型 指定金额
        if ($qrpay_info['type'] == 1) {
            $pay_amount = $qrpay_info['amount'];
        } else {
            $this->validate($request, [
                'amount' => 'required|numeric',
            ]);
            $pay_amount = $request->input('amount', 0);
        }

        $uid = $this->authorization();

        if (empty($uid) && is_wechat_browser()) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $openid = $this->wechatUserService->get_openid($uid);

        $ret = $this->qrpayService->done($uid, $openid, $this->pay_code, $pay_amount, $qrpay_info);

        if (empty($ret)) {
            return $this->responseNotFound();
        }

        $shop_name = $this->merchantCommonService->getShopName($qrpay_info['ru_id'], 1);

        $detail = [
            'seller' => $shop_name,
            'qrcode' => [
                'type' => $qrpay_info['type'],
                'amount' => $pay_amount,
                'qrpay_name' => $qrpay_info['qrpay_name'],
            ],
            'paycode' => $this->pay_code,
            'payment' => $ret ?? ''
        ];
        return $this->succeed($detail);
    }

    /**
     * 支付同步通知
     * @return JsonResponse
     * @throws Exception
     */
    public function callback()
    {
        // 提示类型
        $msg_type = 2;
        $payment = $this->qrpayService->get_payment_info($this->pay_code);
        if ($payment === false) {
            $msg = lang('order.pay_disabled');
        } else {
            if (!empty(request()->isMethod('get'))) {
                try {
                    if ($this->pay_code == 'alipay') {
                        $order = [];
                        list($order['pay_order_sn'], $order['id']) = explode('Q', request()->input('out_trade_no'));
                        $res = $this->orderQuery($order);
                        if ($res === true) {
                            $msg = lang('order.pay_success');
                            $msg_type = 0;
                        } else {
                            $msg = lang('order.pay_fail');
                            $msg_type = 1;
                        }
                    } elseif ($this->pay_code == 'wxpay') {
                        $status = request()->input('status', 0);
                        if ($status == 1) {
                            $msg = lang('order.pay_success');
                            $msg_type = 0;
                        } else {
                            $msg = lang('order.pay_fail');
                            $msg_type = 1;
                        }
                    }
                } catch (PayException $e) {
                    logResult($e->getMessage());
                }
            } else {
                $msg = lang('order.pay_fail');
                $msg_type = 1;
            }
        }

        // 显示页面
        $id = isset($order['id']) ? QrpayLog::where(['id' => $order['id']])->value('qrpay_id') : request()->input('id', 0);

        $detail = [
            'id' => $id,
            'msg' => $msg,
            'msg_type' => $msg_type,
            'page_title' => lang('order.pay_status'),
            'url' => dsc_url('/#/qrpay/' . $id)
        ];
        return $this->succeed($detail);
    }

    /**
     * 支付异步通知
     * @param string $code
     * @param int $id
     * @return array|bool
     */
    public function notify($code = '', $id = 0)
    {
        // 获取code参数
        if ($code) {
            $config = $this->qrpayService->getConfig($code, $id);

            try {
                $callback = app(QrpayOrderPaidNotify::class);
                $trade_type = $code == 'wxpay' ? Config::WX_CHARGE : Config::ALI_CHARGE;
                $ret = Notify::run($trade_type, $config, $callback);// 处理回调，内部进行了签名检查
                return $ret;
            } catch (PayException $e) {
                logResult($e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * 订单查询
     * @param $order
     * @return bool
     */
    public function orderQuery($order)
    {
        $data = [
            'out_trade_no' => $order['pay_order_sn'] . 'Q' . $order['id'],
        ];

        try {
            $trade_type = $this->pay_code == 'wxpay' ? Config::WX_CHARGE : Config::ALI_CHARGE;
            $ret = Query::run($trade_type, $this->qrpayService->getConfig($this->pay_code), $data);
            if (isset($ret['response']) && $ret['response']['trade_state'] === Config::TRADE_STATUS_SUCC) {
                $this->qrpayService->qrpay_order_paid($order['id'], 1);
                return true;
            } else {
                return false;
            }
        } catch (PayException $e) {
            logResult($e->getMessage());
        }

        return false;
    }
}
