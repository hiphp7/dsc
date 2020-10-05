<?php

namespace App\Console\Commands;

use App\Models\OrderInfo;
use App\Models\PayLog;
use App\Models\Payment;
use App\Repositories\Common\CommonRepository;
use Illuminate\Console\Command;

class OrderPayServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:order:pay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order pay select status command';

    protected $commonRepository;

    public function __construct(
        CommonRepository $commonRepository
    )
    {
        parent::__construct();
        $this->commonRepository = $commonRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $order_status = [
            OS_UNCONFIRMED,
            OS_CONFIRMED,
            OS_SPLITED
        ];

        $pay_status = [
            PS_PAYING,
            PS_UNPAYED
        ];

        $res = OrderInfo::whereIn('order_status', $order_status)
            ->whereIn('pay_status', $pay_status);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $value) {
                $pay_log = PayLog::where('order_id', $value['order_id'])->where('is_paid', 0)
                    ->where('order_type', PAY_ORDER)
                    ->first();
                $pay_log = $pay_log ? $pay_log->toArray() : [];
                if ($pay_log && $pay_log['is_paid'] == 0) {

                    $payment = Payment::where('pay_id', $value['pay_id'])->first();
                    $payment = $payment ? $payment->toArray() : [];

                    if ($payment && strpos($payment['pay_code'], 'pay_') === false) {
                        $payObj = $this->commonRepository->paymentInstance($payment['pay_code']);

                        if (!is_null($payObj)) {
                            /* 判断类对象方法是否存在 */
                            if (is_callable([$payObj, 'orderQuery'])) {
                                $order_other = [
                                    'order_sn' => $value['order_sn'],
                                    'log_id' => $pay_log['log_id'],
                                    'order_amount' => $value['order_amount'],
                                ];

                                $payObj->orderQuery($order_other);
                            }
                        }
                    }
                }
            }
        }
    }
}