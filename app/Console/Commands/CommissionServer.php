<?php

namespace App\Console\Commands;

use App\Models\MerchantsPercent;
use App\Models\MerchantsServer;
use App\Models\MerchantsShopInformation;
use App\Models\OrderAction;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\OrderSettlementLog;
use App\Models\SellerBillOrder;
use App\Models\SellerCommissionBill;
use App\Models\SellerNegativeBill;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Commission\CommissionManageService;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderRefoundService;
use Illuminate\Console\Command;

class CommissionServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:commission {action=bill} {--show=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Commission command';

    protected $baseRepository;
    protected $commissionService;
    protected $timeRepository;
    protected $commissionManageService;
    protected $orderCommonService;
    protected $dscRepository;
    protected $orderRefoundService;

    public function __construct(
        BaseRepository $baseRepository,
        CommissionService $commissionService,
        TimeRepository $timeRepository,
        CommissionManageService $commissionManageService,
        OrderCommonService $orderCommonService,
        DscRepository $dscRepository,
        OrderRefoundService $orderRefoundService
    )
    {
        parent::__construct();
        $this->baseRepository = $baseRepository;
        $this->commissionService = $commissionService;
        $this->timeRepository = $timeRepository;
        $this->commissionManageService = $commissionManageService;
        $this->orderCommonService = $orderCommonService;
        $this->dscRepository = $dscRepository;
        $this->orderRefoundService = $orderRefoundService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        $show = (int)$this->option('show'); // 用--开头指定参数名

        if ($action == 'bill') {
            $this->checkBill();
        } elseif ($action == 'charge') {
            $this->commissionBillList();
        } elseif ($action == 'settlement') {
            $this->commissionOrderSettlement($show);
        } elseif ($action == 'sorder') {
            $this->getSellerOrder();
        }
    }

    public function checkBill($seller_id = 0)
    {
        if ($seller_id > 0) {

            $operator = lang('common.manually_create');

            $res = MerchantsShopInformation::select('user_id', 'user_id as seller_id')->where('user_id', $seller_id);
            $res = $res->with(['getMerchantsServer' => function ($query) {
                $query->select('user_id', 'cycle', 'day_number', 'bill_time', 'suppliers_percent');
                $query->with(['getMerchantsPercent' => function ($query) {
                    $query->select('percent_id', 'percent_value');
                }]);
            }]);
            $seller_list = $this->baseRepository->getToArrayGet($res);
            foreach ($seller_list as $key => $value) {
                $value['percent_value'] = '';
                $value['cycle'] = 0;
                $value['day_number'] = '';
                $value['bill_time'] = '';
                if (isset($value['get_merchants_server']) && !empty($value['get_merchants_server'])) {
                    $value['cycle'] = $value['get_merchants_server']['cycle'];
                    $value['day_number'] = $value['get_merchants_server']['day_number'];
                    $value['bill_time'] = $value['get_merchants_server']['bill_time'];
                    $value['commission_model'] = $value['get_merchants_server']['commission_model'] ?? -1;
                    $value['percent_value'] = $value['get_merchants_server']['get_merchants_percent']['percent_value'] ?? 0;
                }

                $seller_list[$key] = $value;
            }
        } else {
            $count = MerchantsShopInformation::where('merchants_audit', 1)->count();

            $seller_list = cache('seller_list');
            $seller_list = !is_null($seller_list) ? $seller_list : false;

            $cache_count = $seller_list ? count($seller_list) : 0;

            $is_cache = 0;
            if ($count && $cache_count && $count > $cache_count) {
                cache()->forget('seller_list');
                $is_cache = 1;
            }

            if ($is_cache == 1 || $seller_list === false) {
                $seller_list = $this->commissionService->getCacheSellerList();
            }

            $operator = lang('order.order_action_user');
        }

        $last_year_start = 0;
        $last_year_end = 0;

        $notime = $this->timeRepository->getGmTime();
        $year = $this->timeRepository->getLocalDate("Y", $notime); //当前年份

        $now_date = $this->timeRepository->getLocalDate("Y-m-d", $notime); //当前年月份
        $year_exp = explode("-", $now_date);
        $nowYear = intval($year_exp[0]); //当前年份
        $nowMonth = intval($year_exp[1]); //当前月份
        $nowDay = intval($year_exp[2]); //当前日期

        if ($seller_list) {
            foreach ($seller_list as $key => $row) {

                $row['seller_id'] = !isset($row['seller_id']) ? $row['user_id'] : $row['seller_id'];

                if (empty($row['percent_value'])) {

                    $merchants_percent = MerchantsPercent::where('percent_value', 100);
                    $merchants_percent = $this->baseRepository->getToArrayFirst($merchants_percent);

                    if (!$merchants_percent) {
                        $merchants_percent = MerchantsPercent::selectRaw('percent_id, IF(percent_value < 100, MAX(percent_value), 100) AS percent_value')
                            ->where('percent_value', '<>', 100)
                            ->orderBy('percent_id', 'desc');
                        $merchants_percent = $this->baseRepository->getToArrayFirst($merchants_percent);
                    }

                    $serverOther = array(
                        'user_id' => $row['seller_id'],
                        'suppliers_percent' => $merchants_percent['percent_id'],
                        'suppliers_desc' => '',
                        'commission_model' => 0,
                        'cycle' => 0,
                        'bill_freeze_day' => 7
                    );

                    $row['percent_value'] = $merchants_percent['percent_value'];
                    $row['cycle'] = $serverOther['cycle'];

                    MerchantsServer::insert($serverOther);
                }

                $is_charge = 1;

                /* ------------------------------------------------------ */
                //-- 按天数
                /* ------------------------------------------------------ */
                if ($row['cycle'] == 7) {
                    $day_array = $this->commissionManageService->getBillDaysNumber($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        $end_time = SellerCommissionBill::where('seller_id', $row['seller_id'])
                            ->where('bill_cycle', $row['cycle'])
                            ->max('end_time');
                        $end_time = $end_time ? $end_time : 0;
                        if ($end_time) {
                            $row['bill_time'] = $end_time;
                        }

                        $last_year_start = $this->timeRepository->getLocalDate("Y-m-d 00:00:00", $row['bill_time']);
                        $bill_time = $row['bill_time'] + ($row['day_number'] - 1) * 24 * 60 * 60;
                        $last_year_end = $this->timeRepository->getLocalDate("Y-m-d 23:59:59", $bill_time);

                        $thistime = $this->timeRepository->getGmTime();
                        $bill_end_time = $this->timeRepository->getLocalStrtoTime($last_year_end);

                        if ($thistime <= $bill_end_time) {
                            $is_charge = 0;
                        }

                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 按年
                /* ------------------------------------------------------ */
                elseif ($row['cycle'] == 6) {
                    $day_array = $this->commissionManageService->getBillOneYear($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        $last_year_start = ($year - 1) . "-01-01 00:00:00"; //去年开始的第一天
                        $last_year_end = ($year - 1) . "-12-31 23:59:59";   //去年结束的最后一天

                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 6个月
                /* ------------------------------------------------------ */
                elseif ($row['cycle'] == 5) {
                    $day_array = $this->commissionManageService->getBillHalfYear($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        /* 判断当前月份是否大于6月份，是否已是七月份 */
                        if ($nowMonth > 6) {
                            $last_year_start = $year . "-01-01 00:00:00"; //当前年份开始的第一天
                            $last_year_end = $year . "-06-30 23:59:59";   //当前年份结束的最后一天
                        } else {

                            /* 获取去年下半年的时间段 */
                            $lastYear = $nowYear - 1;

                            $last_year_start = $lastYear . "-07-01 00:00:00"; //去年后半年开始的第一天
                            $last_year_end = $lastYear . "-12-31 23:59:59";   //后半年结束的最后一天
                        }

                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 1个季度
                /* ------------------------------------------------------ */
                elseif ($row['cycle'] == 4) {
                    $day_array = $this->commissionManageService->getBillQuarter($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        if ($nowMonth > 3 && $nowMonth <= 6) {
                            /* 当前第一季度时间段 */
                            $last_year_start = $nowYear . "-01-01 00:00:00"; //当前第一季度开始的第一天
                            $last_year_end = $nowYear . "-03-31 23:59:59";   //当前第一季度结束的最后一天
                        } elseif ($nowMonth > 6 && $nowMonth <= 9) {
                            /* 当前第二季度时间段 */
                            $last_year_start = $nowYear . "-04-01 00:00:00"; //当前第二季度开始的第一天
                            $last_year_end = $nowYear . "-06-30 23:59:59";   //当前第二季度结束的最后一天
                        } elseif ($nowMonth > 9 && $nowMonth <= 12) {
                            /* 当前第三季度时间段 */
                            $last_year_start = $nowYear . "-07-01 00:00:00"; //当前第三季度开始的第一天
                            $last_year_end = $nowYear . "-09-30 23:59:59";   //当前第三季度结束的最后一天
                        } elseif ($nowMonth <= 3) {
                            /* 当前第四季度时间段 */
                            $last_year_start = $nowYear - 1 . "-10-01 00:00:00"; //当前第四季度开始的第一天
                            $last_year_end = $nowYear - 1 . "-12-31 23:59:59";   //当前第四季度结束的最后一天
                        }

                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 1个月
                /* ------------------------------------------------------ */
                elseif ($row['cycle'] == 3) {
                    $day_array = $this->commissionManageService->getBillOneMonth($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        $nowMonth = $nowMonth - 1;

                        /* 获取当月天数 */
                        $days = $this->timeRepository->getCalDaysInMonth(CAL_GREGORIAN, $nowMonth, $nowYear);

                        if ($nowMonth <= 9) {
                            $newNowMonth = "0" . $nowMonth;
                        } else {
                            $newNowMonth = $nowMonth;
                        }

                        $last_year_start = $nowYear . "-" . $newNowMonth . "-01 00:00:00"; //上一个月的第一天
                        $last_year_end = $nowYear . "-" . $newNowMonth . "-" . $days . " 23:59:59"; //上一个月的最后一天

                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 15天（半个月）
                /* ------------------------------------------------------ */
                elseif ($row['cycle'] == 2) {
                    $day_array = $this->commissionManageService->getBillHalfMonth($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        $lastDay = $this->timeRepository->getLocalDate('Y-m-t');
                        $lastDay = explode("-", $lastDay);
                        $halfDay = intval($lastDay[2] / 2);

                        if ($nowDay > $halfDay) {
                            $last_year_start = $lastDay[0] . "-" . $lastDay[1] . "-01 00:00:00"; //当前月开始的第一天
                            $last_year_end = $lastDay[0] . "-" . $lastDay[1] . "-" . $halfDay . " 23:59:59"; //当前月开始的第一天
                        } else {
                            $lastMonth_firstDay = $nowYear . "-" . $nowMonth . "-01 00:00:00";
                            $lastMonth_lastDay = $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime("$lastMonth_firstDay +1 month -1 day")) . " 23:59:59";

                            $lastMonth = $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime("$lastMonth_firstDay +1 month -1 day"));
                            $lastMonth = explode("-", $lastMonth);
                            $halfMonth = intval($lastMonth[2] / 2);
                            $middleMonth = $lastMonth[0] . "-" . $lastMonth[1] . "-" . ($halfMonth + 1);

                            $middleMonth_firstDay = $middleMonth . " 00:00:00";
                            $last_year_start = $middleMonth_firstDay;   //当前月月中的天数日期
                            $last_year_end = $lastMonth_lastDay;    //上一个月的最后一天（以当前是5月15号之前运算）
                        }
                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 七天(按一个礼拜)
                /* ------------------------------------------------------ */
                elseif ($row['cycle'] == 1) {
                    $day_array = $this->commissionManageService->getBillSevenDay($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        $week = $this->timeRepository->getLocalDate('w'); //当前月的日期本周
                        $thisWeekMon = $this->timeRepository->getLocalStrtoTime('+' . 1 - $week . ' days'); //本周礼拜一
                        $lastWeekMon = 7 * 24 * 60 * 60; //上个礼拜一的时间
                        $lastWeeksun = 1 * 24 * 60 * 60; //上个礼拜日的时间

                        $lastWeekMon = $thisWeekMon - $lastWeekMon;
                        $lastWeeksun = $thisWeekMon - $lastWeeksun;

                        $last_year_start = $this->timeRepository->getLocalDate('Y-m-d 00:00:00', $lastWeekMon);
                        $last_year_end = $this->timeRepository->getLocalDate('Y-m-d 23:59:59', $lastWeeksun);
                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                /* ------------------------------------------------------ */
                //-- 每天
                /* ------------------------------------------------------ */
                else {
                    $day_array = $this->commissionManageService->getBillPerDay($row['seller_id'], $row['cycle']);

                    if (empty($day_array)) {
                        $last_year_start = $this->timeRepository->getLocalDate("Y-m-d 00:00:00", $this->timeRepository->getLocalStrtoTime("-1 day"));
                        $last_year_end = $this->timeRepository->getLocalDate("Y-m-d 23:59:59", $this->timeRepository->getLocalStrtoTime("-1 day"));
                        $day_array[0]['last_year_start'] = $last_year_start;
                        $day_array[0]['last_year_end'] = $last_year_end;
                    }
                }

                if ($day_array) {
                    foreach ($day_array as $keys => $rows) {
                        $last_year_start = $this->timeRepository->getLocalStrtoTime($rows['last_year_start']); //时间戳
                        $last_year_end = $this->timeRepository->getLocalStrtoTime($rows['last_year_end']); //时间戳

                        $bill_count = SellerCommissionBill::where('seller_id', $row['seller_id'])
                            ->where('bill_cycle', $row['cycle'])
                            ->where('start_time', '>=', $last_year_start)
                            ->where('end_time', '<=', $last_year_end)
                            ->count();

                        if ($is_charge == 1 && ($last_year_start > 0 && $last_year_end > 0 && $last_year_start < $last_year_end)) {
                            if ($bill_count <= 0) {
                                $bill_sn = $this->orderCommonService->getOrderSn();

                                /* 处理重复订单账单号 */
                                $sn_count = SellerCommissionBill::where('bill_sn', $bill_sn)->count();
                                if ($sn_count > 0) {
                                    $bill_sn += 1;
                                }

                                $other = [
                                    'seller_id' => $row['seller_id'],
                                    'bill_sn' => $bill_sn,
                                    'proportion' => $row['percent_value'],
                                    'commission_model' => $row['commission_model'] ?? -1,
                                    'start_time' => $last_year_start,
                                    'end_time' => $last_year_end,
                                    'bill_cycle' => $row['cycle'],
                                    'operator' => $operator
                                ];

                                SellerCommissionBill::insert($other);
                            }
                        }
                    }
                }

                $this->commissionManageService->negativeBill($row['seller_id']);
            }
        }
    }

    /**
     * 账单列表
     *
     * @param int $seller_id
     */
    public function commissionBillList($seller_id = 0)
    {

        $page_size = 10;

        $totals = SellerCommissionBill::where('chargeoff_status', 0);

        if ($seller_id > 0) {
            $totals = $totals->where('seller_id', $seller_id);
        }

        $totals = $totals->count();
        $totals = $totals ? $totals : 0;

        $countpage = ceil($totals / $page_size); #计算总页面数

        /* 查询 */
        $row = SellerCommissionBill::where('chargeoff_status', 0);

        if ($seller_id > 0) {
            $row = $row->where('seller_id', $seller_id);
        }

        $row = $this->baseRepository->getToArrayGet($row);

        $cache_name = 'commission_bill_server';
        $this->dscRepository->writeStaticCache('', $cache_name, $row, 'commission/bill/');

        $gmtime = $this->timeRepository->getGmTime();

        if ($row) {

            for ($page = 1; $countpage >= $page; $page++) {
                $list = $this->dscRepository->readStaticCache('', $cache_name, 'commission/bill/');

                $arr = $this->dscRepository->pageArray($page_size, $page, $list);

                if ($arr['list']) {
                    foreach ($arr['list'] as $key => $value) {
                        //未出账单
                        if (empty($value['chargeoff_status'])) {
                            $detail = $this->commissionService->getBillAmountDetail($value['id'], $value['seller_id'], $value['proportion'], $value['start_time'], $value['end_time'], $value['chargeoff_status'], $value['commission_model']);

                            //出账单，绑定满足账单订单 start
                            if ($detail && $value['end_time'] < $gmtime) {
                                $other['chargeoff_status'] = 1;
                                $other['order_amount'] = $detail['order_amount'];
                                $other['shipping_amount'] = $detail['shipping_amount'];
                                $other['return_amount'] = $detail['return_amount'];
                                $other['return_shippingfee'] = $detail['return_shippingfee'];
                                $other['return_rate_fee'] = $detail['return_rate_fee'];
                                $other['gain_commission'] = $detail['gain_commission'];
                                $other['should_amount'] = $detail['should_amount'];
                                $other['drp_money'] = $detail['drp_money'];
                                $other['commission_model'] = $detail['commission_model'];
                                $other['chargeoff_time'] = $this->timeRepository->getGmTime();
                                $other['rate_fee'] = $detail['rate_fee'];

                                SellerCommissionBill::where('id', $value['id'])->update($other);

                                /* 更新负账单 */
                                if ($detail['should_amount'] > 0) {

                                    $negative_bill = $this->commissionService->getNegativeBllTotal($value['seller_id'], $value['end_time']);

                                    if (isset($negative_bill['negative_id']) && !empty($negative_bill['negative_id'])) {

                                        $negative_id = $this->baseRepository->getExplode($negative_bill['negative_id']);

                                        $negativeOther = [
                                            'commission_bill_id' => $value['id'],
                                            'commission_bill_sn' => $value['bill_sn']
                                        ];

                                        $is_negative = SellerNegativeBill::whereIn('id', $negative_id)
                                            ->where('return_amount', '<=', $detail['should_amount'])
                                            ->update($negativeOther);

                                        if ($is_negative) {
                                            if (isset($negative_bill['total']) && $negative_bill['total'] > 0) {
                                                $negativeBillOther['negative_amount'] = $negative_bill['total'];
                                                $negativeBillOther['should_amount'] = $other['should_amount'] - $negative_bill['total'];
                                                SellerCommissionBill::where('id', $value['id'])->update($negativeBillOther);
                                            }
                                        }
                                    }
                                }

                                $row[$key]['chargeoff_status'] = $other['chargeoff_status'];

                                $bill_order_other['bill_id'] = $value['id'];
                                $bill_order_other['chargeoff_status'] = $other['chargeoff_status'];

                                $billOrderUpdate = SellerBillOrder::where('confirm_take_time', '>=', $value['start_time'])
                                    ->where('confirm_take_time', '<=', $value['end_time'])
                                    ->where('seller_id', $value['seller_id'])
                                    ->where('chargeoff_status', '<>', 2)
                                    ->where('bill_id', 0);
                                $billOrderUpdate = $this->orderCommonService->orderQuerySelect($billOrderUpdate, 'confirm_take');
                                $billOrderUpdate->update($bill_order_other);

                                $order_list = SellerBillOrder::select('order_id')->where('bill_id', $value['id']);
                                $order_list = $this->baseRepository->getToArrayGet($order_list);
                                $order_list = $this->baseRepository->getKeyPluck($order_list, 'order_id');

                                if ($order_list) {
                                    OrderInfo::whereIn('order_id', $order_list)->update([
                                        'chargeoff_status' => $other['chargeoff_status']
                                    ]);

                                    OrderReturn::whereIn('order_id', $order_list)->update([
                                        'chargeoff_status' => $other['chargeoff_status']
                                    ]);
                                }
                            }
                            //出账单，绑定满足账单订单 end
                        }
                    }
                }
            }
        }
    }

    /**
     * 更新账单订单佣金
     *
     * @param int $show_string
     */
    public function commissionOrderSettlement($show_string = 0)
    {
        $list = SellerBillOrder::select('order_id', 'seller_id', 'order_sn');

        if ($show_string == 0) {
            $list = $list->where('chargeoff_status', '<', 2);
        }

        $list = $list->with([
            'getOrder',
            'getSellerCommissionBill'
        ]);

        $list = $this->baseRepository->getToArrayGet($list);

        if ($list) {
            foreach ($list as $key => $row) {

                $filter = [
                    'order_sn' => $row['order_sn']
                ];

                /* 微分销 */
                if (file_exists(MOBILE_DRP)) {
                    $no_settlement = $this->commissionService->merchantsIsSettlement($row['seller_id'], '', $filter);
                } else {
                    $no_settlement = $this->commissionService->merchantsIsSettlement($row['seller_id'], '', $filter);
                }

                $gain_amount = $no_settlement['all_gain_commission'] ?? 0;
                $gain_amount = $this->dscRepository->changeFloat($gain_amount);

                $actual_amount = $no_settlement['all_price'] ?? 0;
                $actual_amount = $this->dscRepository->changeFloat($actual_amount);

                $log = [
                    'order_id' => $row['order_id'],
                    'ru_id' => $row['seller_id'],
                    'gain_amount' => $gain_amount,
                    'actual_amount' => $actual_amount,
                    'is_settlement' => $row['get_order']['is_settlement'] ?? 0,
                    'add_time' => $this->timeRepository->getGmTime()
                ];

                $count = OrderSettlementLog::where('order_id', $row['order_id'])->count();

                if ($show_string == 1) {
                    if ($count < 1) {
                        OrderSettlementLog::insert($log);

                        var_dump("账单订单结算记录数据执行成功。");
                    } else {
                        var_dump("账单订单结算记录数据执行失败，已存在。");
                    }
                } else {
                    if ($count < 1) {
                        OrderSettlementLog::insert($log);
                    } else {
                        $log['update_time'] = $this->timeRepository->getGmTime();
                        OrderSettlementLog::where('order_id', $row['order_id'])->where('is_settlement', 0)
                            ->update($log);
                    }
                }
            }
        }
    }

    /**
     * 查询账单订单数据是否存在
     *
     * 不存在重新插入
     */
    public function getSellerOrder()
    {
        $order_status = [
            defined(OS_CONFIRMED) ? OS_CONFIRMED : 1,
            defined(OS_RETURNED_PART) ? OS_RETURNED_PART : 7,
            defined(OS_SPLITED) ? OS_SPLITED : 5
        ];

        $shipping_status = defined(SS_RECEIVED) ? SS_RECEIVED : 2;
        $pay_status = defined(PS_PAYED) ? PS_PAYED : 2;

        $res = OrderInfo::whereIn('order_status', $order_status)
            ->where('shipping_status', $shipping_status)
            ->where('pay_status', $pay_status);

        $res = $res->doesntHave('getSellerBillOrder');

        $res = $res->with([
            'getSellerNegativeOrder'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $value) {
                $value_card = $value['get_value_card_record']['use_val'] ?? '';

                if (empty($value['get_seller_negative_order'])) {
                    $return_amount_info = $this->orderRefoundService->orderReturnAmount($value['order_id']);
                } else {
                    $return_amount_info['return_amount'] = 0;
                    $return_amount_info['return_rate_fee'] = 0;
                    $return_amount_info['ret_id'] = [];
                }

                if ($value['confirm_take_time']) {
                    $confirm_take_time = $value['confirm_take_time'];
                } else {
                    $log_time = OrderAction::where('order_id', $value['order_id'])->where('shipping_status', $value['shipping_status'])->value('log_time');
                    $log_time = $log_time ? $log_time : 0;

                    if ($log_time) {
                        $confirm_take_time = $log_time;
                    } else {
                        $confirm_take_time = $this->timeRepository->getGmTime();
                    }

                    OrderInfo::where('order_id', $value['order_id'])->update([
                        'confirm_take_time' => $confirm_take_time
                    ]);
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
                    'shipping_status' => $value['shipping_status'],
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
                    'return_rate_fee' => $return_amount_info['return_rate_price'] ?? 0
                );

                if ($value['user_id']) {
                    $this->commissionService->getOrderBillLog($other);
                    $this->commissionService->setBillOrderReturn($return_amount_info['ret_id'], $other['order_id']);
                }
            }
        }

    }
}