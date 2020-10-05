<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\UserAccount;
use App\Models\Users;
use App\Models\UsersReal;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Payment\PaymentService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Class AccountController
 * @package App\Api\Fourth\Controllers
 */
class AccountController extends Controller
{
    protected $config;
    protected $baseRepository;
    protected $userService;
    protected $paymentService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        UserService $userService,
        PaymentService $paymentService,
        DscRepository $dscRepository
    )
    {
        $this->userService = $userService;
        $this->baseRepository = $baseRepository;
        $this->paymentService = $paymentService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    protected function initialize()
    {
        parent::initialize();
        //加载外部类
        $files = [
            'clips',
            'common',
            'time',
            'main',
            'order',
            'function',
            'payment',
            'base',
        ];

        load_helper($files);

        //加载语言包
        $this->dscRepository->helpersLang('user');
    }

    /**
     * 账户概要
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request)
    {
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //会员账户信息
        $user_account = get_row_user_account($user_id);
        if ($user_account) {
            $user_account['value_card']['use_value_card'] = $this->config['use_value_card'];
        }
        return $this->succeed($user_account);
    }

    /**
     * 申请记录
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function replylog(Request $request)
    {
        $page = $request->get('page', 1);

        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        /* 获取记录条数 */
        $record_count = UserAccount::where('user_id', $user_id)->whereIn('process_type', [SURPLUS_SAVE, SURPLUS_RETURN])->count();

        //分页函数
        $pager = get_pager('user.php', ['act' => 'account_log'], $record_count, $page);

        $account_log = $this->userService->getAccountLog($user_id, $pager['size'], $pager['start']);

        return $this->succeed($account_log);
    }

    /**
     * 账户明细
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function accountlog(Request $request)
    {
        $page = $request->input('page', 1);
        $size = $request->input('size', 15);

        $account_type = $request->input('account_type', 'user_money');

        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        /* 获取记录条数 */
        $record_count = $this->userService->GetUserAccountLogCount($user_id, $account_type);

        //分页函数
        $pager = get_pager('user.php', ['act' => 'account_detail'], $record_count, $page, $size);

        // //获取余额记录
        $account_log = $this->userService->GetUserAccountLogList($user_id, $account_type, $pager);

        return $this->succeed($account_log);
    }

    /**
     * 资金提现
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function reply(Request $request)
    {
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        // 检测是否实名认证
        $usersReal = UsersReal::where('user_id', $user_id)->where('user_type', 0);
        $usersReal = $this->baseRepository->getToArrayFirst($usersReal);

        $res['code'] = 0;
        if (empty($usersReal)) {
            $res['code'] = 1;
            $res['msg'] = $GLOBALS['_LANG']['16_no_users_real_desc'];
            return $this->succeed($res);
        }

        if ($usersReal['review_status'] != 1) {
            $res['code'] = 2;
            $res['msg'] = $GLOBALS['_LANG']['16_users_real'] . $GLOBALS['_LANG']['is_confirm'][$usersReal['review_status']];
            return $this->succeed($res);
        }

        // 获取剩余余额
        $surplus_amount = get_user_surplus($user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }

        $res['buyer_cash'] = intval($this->config['buyer_cash']);

        // 组装提现卡号，二维数组便于模板循环
        $bank = [
            [
                'bank_name' => $usersReal['bank_name'],
                'bank_card' => substr($usersReal['bank_card'], 0, 4) . '******' . substr($usersReal['bank_card'], -4),
                'bank_region' => $usersReal['bank_name'],
                'bank_user_name' => $usersReal['real_name'],
                'bank_card_org' => $usersReal['bank_card'],
                'bank_mobile' => $usersReal['bank_mobile'],
            ]
        ];

        $res['surplus_amount'] = price_format($surplus_amount, false);
        $res['deposit_fee'] = $this->config['deposit_fee'];
        $res['page_title'] = 'account_user_repay';
        $res['bank'] = $bank;
        $res['code'] = 0;

        return $this->succeed($res);
    }

    /**
     * 账户充值
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function deposit(Request $request)
    {
        $user_id = $this->authorization();

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $surplus_id = $request->input('id', 2);

        $account = get_surplus_info($surplus_id, $user_id);

        $res = [
            'payment' => $this->paymentService->availablePaymentList(0, 0, 1),
            'order' => $account,
            'process_type' => $surplus_id,
            'page_title' => 'account_user_charge'
        ];

        return $this->succeed($res);
    }

    /**
     * 充值提现操作
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function account(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'amount' => 'required|numeric',
            //'payment_id' => 'required|integer',
        ]);

        $payment_id = $request->input('payment_id', 0);
        $user_note = $request->input('user_note', '');
        $amount = $request->input('amount', 0);
        $rec_id = $request->input('rec_id', 0);
        $surplus_type = $request->input('surplus_type', 0); // 操作类型：0为充值，1为提现

        $res['code'] = 0;

        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        /* 变量初始化 */
        $surplus = [
            'user_id' => $user_id,
            'rec_id' => $rec_id,
            'process_type' => $surplus_type,
            'payment_id' => $payment_id,
            'user_note' => $user_note,
            'amount' => $amount,
        ];

        if ($surplus['process_type'] == 1) {
            $usersReal = UsersReal::where('user_id', $user_id)
                ->where('user_type', 0)
                ->where('review_status', 1);
            $usersReal = $this->baseRepository->getToArrayFirst($usersReal);

            if (empty($usersReal)) {
                $res['code'] = 1;
                $res['msg'] = $GLOBALS['_LANG']['16_users_real_desc'];
                return $this->succeed($res);
            }

            $buyer_cash = intval($this->config['buyer_cash']); // 买家提现最低金额，0表示不限

            if (!empty($buyer_cash) && $amount < $buyer_cash) {
                $res['code'] = 2;
                $res['msg'] = $GLOBALS['_LANG']['forward_total_lowest'] . ":" . $buyer_cash;
                return $this->succeed($res);
            }

            /* 判断是否有足够的余额的进行退款的操作 */
            $sur_amount = get_user_surplus($user_id);
            if ($amount > $sur_amount) {
                $res['code'] = 3;
                $res['msg'] = $GLOBALS['_LANG']['surplus_amount_error'];
                return $this->succeed($res);
            }

            if ($usersReal && (empty($usersReal['bank_card']) || empty($usersReal['real_name']))) {
                $res['code'] = 4;
                $res['msg'] = $GLOBALS['_LANG']['users_real_no_complete'];
                return $this->succeed($res);
            }

            $deposit_fee = $this->config['deposit_fee'] ?? 0; // 提现手续费比例
            $deposit_money = 0;
            if ($deposit_fee > 0) {
                $deposit_money = $amount * $deposit_fee / 100;
            }

            //判断手续费扣除模式，余额充足则从余额中扣除手续费，不足则在提现金额中扣除
            if (($amount + $deposit_money) > $sur_amount) {
                $amount = $amount - $deposit_money;
            }

            //插入会员账目明细
            $surplus['deposit_fee'] = '-' . $deposit_money;

            //提现金额
            $frozen_money = $amount + $deposit_money;
            $amount = '-' . $amount;
            $surplus['payment'] = '';
            $surplus['rec_id'] = insert_user_account($surplus, $amount);

            /* 如果成功提交 */
            if ($surplus['rec_id'] > 0) {
                //by wang提现记录扩展信息start
                $user_account_fields = [
                    'user_id' => $surplus['user_id'],
                    'account_id' => $surplus['rec_id'],
                    'bank_number' => $usersReal['bank_card'],
                    'real_name' => $usersReal['real_name']
                ];

                insert_user_account_fields($user_account_fields);
                //by wang提现记录扩展信息end

                /* 申请提现的资金进入冻结状态 */
                log_account_change($user_id, $amount, $frozen_money, 0, 0, "【" . $GLOBALS['_LANG']['application_withdrawal'] . "】" . $surplus['user_note'], ACT_ADJUSTING, 0, $surplus['deposit_fee']);

                $res['msg'] = $GLOBALS['_LANG']['surplus_appl_submit'];
            }
        } else {
            $buyer_recharge = intval($this->config['buyer_recharge']); // 买家充值最低金额，0表示不限
            if (!empty($buyer_recharge) && $amount < $buyer_recharge) {
                $res['code'] = 1;
                $res['msg'] = $GLOBALS['_LANG']['user_recharge_notic'] . "：" . $buyer_recharge;
                return $this->succeed($res);
            }

            if ($surplus['payment_id'] <= 0) {
                $res['code'] = 2;
                $res['msg'] = $GLOBALS['_LANG']['js_languages']['select_payment_pls'];
                return $this->succeed($res);
            }

            //获取支付方式名称
            $payment_info = payment_info($surplus['payment_id']);
            $surplus['payment'] = $payment_info['pay_name'];
            if ($surplus['rec_id'] > 0) {
                //更新会员账目明细
                $surplus['rec_id'] = update_user_account($surplus);
            } else {
                //插入会员账目明细
                $surplus['rec_id'] = insert_user_account($surplus, $amount);
            }

            //取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);

            //生成伪订单号, 不足的时候补0
            $order = [];
            $order['order_sn'] = $surplus['rec_id'];
            $order['user_name'] = Users::where('user_id', $user_id)->value('user_name');
            $order['surplus_amount'] = $amount;
            $order['subject'] = lang('user.account_deposit');// 充值描述

            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

            //计算此次预付款需要支付的总金额
            $order['order_amount'] = $amount + $payment_info['pay_fee'];

            if ($order['order_amount'] > 0 && $payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                $pay_name = Str::studly($payment_info['pay_code']);
                $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                //记录支付log
                $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], PAY_SURPLUS, 0);

                if (!is_null($pay_obj)) {
                    $res['pay_button'] = $pay_obj->get_code($order, $payment, $user_id);
                }
            }
        }

        return $this->succeed($res);
    }

    /**
     * 个人积分明细
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function paypoints(Request $request)
    {
        // 获取会员id
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $page = $request->input('page', 1);
        $type = $request->input('type', 'pay_points');
        $size = $request->input('size', 10);

        $list = $this->userService->getUserPayPoints($user_id, $page, $size, $type);

        return $this->succeed($list);
    }
}
