<?php

namespace App\Console\Commands;

use App\Models\OrderAction;
use App\Models\OrderInfo;
use App\Models\SellerBillOrder;
use App\Models\UserOrderNum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderRefoundService;
use Illuminate\Console\Command;

class OrderDeliveryServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:order:delivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order delivery select status command';

    protected $commonRepository;
    protected $timeRepository;
    protected $orderCommonService;
    protected $orderRefoundService;
    protected $commissionService;
    protected $baseRepository;

    public function __construct(
        CommonRepository $commonRepository,
        TimeRepository $timeRepository,
        OrderCommonService $orderCommonService,
        OrderRefoundService $orderRefoundService,
        CommissionService $commissionService,
        BaseRepository $baseRepository
    )
    {
        parent::__construct();
        $this->commonRepository = $commonRepository;
        $this->timeRepository = $timeRepository;
        $this->orderCommonService = $orderCommonService;
        $this->orderRefoundService = $orderRefoundService;
        $this->commissionService = $commissionService;
        $this->baseRepository = $baseRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $order_status = [
            OS_CONFIRMED,
            OS_SPLITED,
            OS_RETURNED_PART,
            OS_ONLY_REFOUND
        ];

        $res = OrderInfo::whereIn('order_status', $order_status)
            ->where('pay_status', PS_PAYED)->where('shipping_status', SS_SHIPPED)
            ->where('chargeoff_status', 0);

        $res = $res->with([
            'getValueCardRecord',
            'getSellerNegativeOrder'
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $value) {
                $delivery_time = $value['shipping_time'] + 24 * 3600 * $value['auto_delivery_time'];

                $confirm_take_time = $this->timeRepository->getGmTime();

                if ($confirm_take_time > $delivery_time) { //自动确认发货操作

                    $data = [
                        'order_status' => $value['order_status'],
                        'shipping_status' => SS_RECEIVED,
                        'pay_status' => $value['pay_status'],
                        'confirm_take_time' => $confirm_take_time
                    ];
                    OrderInfo::where('order_id', $value['order_id'])->update($data);

                    /* 更新会员订单信息 */
                    $dbRaw = [
                        'order_nogoods' => "order_nogoods - 1",
                        'order_isfinished' => "order_isfinished + 1"
                    ];
                    $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                    UserOrderNum::where('user_id', $value['user_id'])->update($dbRaw);

                    /* 记录日志 */
                    $note = lang('admin/order.self_motion_goods');
                    $this->orderCommonService->orderAction($value['order_sn'], $value['order_status'], SS_RECEIVED, $value['pay_status'], $note, lang('admin/common.system_handle'), 0, $confirm_take_time);

                    $bill_order_info = SellerBillOrder::where('order_id', $value['order_id'])->count();

                    if ($bill_order_info <= 0) {

                        $value_card = $value['get_value_card_record']['use_val'] ?? '';

                        if (empty($value['get_seller_negative_order'])) {
                            $return_amount_info = $this->orderRefoundService->orderReturnAmount($value['order_id']);
                        } else {
                            $return_amount_info['return_amount'] = 0;
                            $return_amount_info['return_rate_fee'] = 0;
                            $return_amount_info['ret_id'] = [];
                        }

                        if ($value['order_amount'] > 0 && $value['order_amount'] > $value['rate_fee']) {
                            $order_amount = $value['order_amount'] - $value['rate_fee'];
                        } else {
                            $order_amount = $value['order_amount'];
                        }

                        $other = array(
                            'user_id' => $value['user_id'],
                            'seller_id' => $value['ru_id'],
                            'order_id' => $value['order_id'],
                            'order_sn' => $value['order_sn'],
                            'order_status' => $value['order_status'],
                            'shipping_status' => SS_RECEIVED,
                            'pay_status' => $value['pay_status'],
                            'order_amount' => $order_amount,
                            'return_amount' => $return_amount_info['return_amount'],
                            'goods_amount' => $value['goods_amount'],
                            'tax' => $value['tax'],
                            'shipping_fee' => $value['shipping_fee'],
                            'insure_fee' => $value['insure_fee'],
                            'pay_fee' => $value['pay_fee'] ?? 0,
                            'pack_fee' => $value['pack_fee'] ?? 0,
                            'card_fee' => $value['card_fee'] ?? 0,
                            'bonus' => $value['bonus'],
                            'integral_money' => $value['integral_money'] ?? 0,
                            'coupons' => $value['coupons'],
                            'discount' => $value['discount'],
                            'value_card' => $value_card ? $value_card : 0,
                            'money_paid' => $value['money_paid'],
                            'surplus' => $value['surplus'],
                            'confirm_take_time' => $confirm_take_time,
                            'rate_fee' => $value['rate_fee'],
                            'return_rate_fee' => $return_amount_info['return_rate_price']
                        );

                        if ($value['user_id']) {
                            $this->commissionService->getOrderBillLog($other);
                            $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                        }
                    }
                }

                if (empty($value['confirm_take_time'])) {
                    $bill_order_count = SellerBillOrder::where('order_id', $value['order_id'])->count();

                    if ($bill_order_count > 0 && $value['shipping_status'] == SS_RECEIVED) {

                        $confirm_take_time = OrderAction::where('order_id', $value['order_id'])
                            ->where('shipping_status', SS_RECEIVED)
                            ->max('log_time');
                        $confirm_take_time = $confirm_take_time ? $confirm_take_time : '';

                        if (empty($confirm_take_time)) {
                            $confirm_take_time = $this->timeRepository->getGmTime();

                            $note = lang('admin/order.admin_order_list_motion');
                            $this->orderCommonService->orderAction($value['order_sn'], $value['order_status'], $value['shipping_status'], $value['pay_status'], $note, lang('admin/common.system_handle'), 0, $confirm_take_time);
                        }

                        $log_other = array(
                            'confirm_take_time' => $confirm_take_time
                        );

                        OrderInfo::where('order_id', $value['order_id'])->update($log_other);

                        SellerBillOrder::where('order_id', $value['order_id'])->update($log_other);
                    }
                }
            }
        }
    }
}
