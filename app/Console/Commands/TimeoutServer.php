<?php

namespace App\Console\Commands;

use App\Models\AccountLog;
use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\GoodsInventoryLogs;
use App\Models\OrderAction;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PackageGoods;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\ReturnAction;
use App\Models\SeckillGoods;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Models\UserBonus;
use App\Models\UserOrderNum;
use App\Models\Users;
use App\Models\ValueCard;
use App\Models\ValueCardRecord;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\User\UserRankService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TimeoutServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:timeout {action=pay}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Timeout order pay select status command';

    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $config;
    protected $userRankService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        UserRankService $userRankService
    )
    {
        parent::__construct();
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->userRankService = $userRankService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');

        if ($action == 'pay') {
            //订单时效
            $pay_effective_time = isset($this->config['pay_effective_time']) && $this->config['pay_effective_time'] > 0 ? intval($this->config['pay_effective_time']) : 0;
            $this->timeoutOrderPay($pay_effective_time);
        }
    }

    //处理支付超时订单
    private function timeoutOrderPay($pay_effective_time = 0)
    {
        if ($pay_effective_time > 0) {
            $pay_effective_time = $pay_effective_time * 60;
            $time = $this->timeRepository->getGmTime();

            $totals = OrderInfo::where('main_count', 0);

            $totals = $totals->whereHas('getPayment', function ($query) {
                $query->whereNotIn('pay_code', ['bank', 'cod', 'post']);
            });

            $totals = $totals->whereRaw("($time - add_time) > $pay_effective_time")
                ->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED])
                ->whereIn('shipping_status', [SS_UNSHIPPED, SS_PREPARING])
                ->where('pay_status', PS_UNPAYED);

            $totals = $totals->count();

            $page_size = 10;
            $countpage = ceil($totals / $page_size); #计算总页面数

            $cache_time = $this->timeRepository->getLocalDate('Y-m-d');
            $cache_name = 'timeout_order_pay_' . $cache_time;
            $list = cache($cache_name);
            $list = !is_null($list) ? $list : [];

            if (empty($list)) {

                $list = OrderInfo::where('main_count', 0);

                $list = $list->whereHas('getPayment', function ($query) {
                    $query->whereNotIn('pay_code', ['bank', 'cod', 'post']);
                });

                $list = $list->whereRaw("($time - add_time) > $pay_effective_time")
                    ->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED])
                    ->whereIn('shipping_status', [SS_UNSHIPPED, SS_PREPARING])
                    ->where('pay_status', PS_UNPAYED);

                $list = $list->with([
                    'getStoreOrder'
                ]);

                $list = $this->baseRepository->getToArrayGet($list);

                cache()->forever($cache_name, $list);
            }

            $cache_name_file = 'timeout_order_pay_cache_' . $cache_time;
            $this->dscRepository->writeStaticCache('', $cache_name_file, $list, 'user_order_timeout/');

            if ($list) {

                for ($page = 1; $countpage >= $page; $page++) {

                    $list = $this->dscRepository->readStaticCache('', $cache_name_file, 'user_order_timeout/');

                    $arr = $this->dscRepository->pageArray($page_size, $page, $list);
                    $order_list = $arr['list'] ?? [];

                    if (!empty($order_list)) {
                        foreach ($order_list as $k => $v) {
                            $store_id = $order_list['get_store_order']['store_id'] ?? 0;

                            /* 标记订单为“无效” */
                            OrderInfo::where('order_id', $v['order_id'])->update([
                                'order_status' => OS_INVALID
                            ]);

                            /* 记录log */
                            $this->orderAction($v['order_id'], OS_INVALID, SS_UNSHIPPED, PS_UNPAYED, lang('order.order_pay_timeout'));

                            /* 如果使用库存，且下订单时减库存，则增加库存 */
                            if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PLACE) {
                                $this->changeOrderGoodsStorage($v['order_id'], false, SDT_PLACE, 2, 0, $store_id);
                            }

                            /* 退还用户余额、积分、红包 */
                            $this->returnUserSurplusIntegralBonus($v);

                            /* 更新会员订单数量 */
                            if (isset($v['user_id']) && !empty($v['user_id'])) {
                                $order_nopay = UserOrderNum::where('user_id', $v['user_id'])->value('order_nopay');
                                $order_nopay = $order_nopay ? intval($order_nopay) : 0;

                                if ($order_nopay > 0) {
                                    $dbRaw = [
                                        'order_nopay' => "order_nopay - 1",
                                    ];
                                    $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                                    UserOrderNum::where('user_id', $v['user_id'])->update($dbRaw);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 生成日志
     *
     * @param int $order_id
     * @param int $order_status
     * @param int $shipping_status
     * @param int $pay_status
     * @param string $note
     * @throws \Exception
     */
    private function orderAction($order_id = 0, $order_status = 0, $shipping_status = 0, $pay_status = 0, $note = '')
    {
        $log_time = $this->timeRepository->getGmTime();

        if ($order_id > 0) {

            $other = [
                'order_id' => $order_id,
                'action_user' => lang('order.order_action_user'),
                'order_status' => $order_status,
                'shipping_status' => $shipping_status,
                'pay_status' => $pay_status,
                'action_note' => $note,
                'log_time' => $log_time
            ];
            OrderAction::insert($other);
        }
    }

    /**
     * 改变订单中商品库存
     *
     * @param $order_id 是否减少库存
     * @param bool $is_dec
     * @param int $storage 减库存的时机，2，付款时； 1，下订单时；0，发货时；
     * @param int $use_storage
     * @param int $admin_id
     * @param int $store_id
     */
    private function changeOrderGoodsStorage($order_id, $is_dec = true, $storage = 0, $use_storage = 0, $admin_id = 0, $store_id = 0)
    {
        $select = '';

        /* 查询订单商品信息 */
        switch ($storage) {
            case 0:
                $select = "goods_id, send_number AS num, extension_code, product_id, warehouse_id, area_id, area_city";
                break;

            case 1:
            case 2:
                $select = "goods_id, goods_number AS num, extension_code, product_id, warehouse_id, area_id, area_city";
                break;
        }

        $res = [];
        if ($select) {
            $res = OrderGoods::selectRaw($select)
                ->where('order_id', $order_id)
                ->where('is_real', 1);
            $res = $this->baseRepository->getToArrayGet($res);
        }

        if ($res) {
            foreach ($res as $row) {
                if ($row['extension_code'] != "package_buy") {
                    if ($is_dec) {
                        $this->changeGoodsStorage($row['goods_id'], $row['product_id'], -$row['num'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id, $store_id);
                    } else {
                        $this->changeGoodsStorage($row['goods_id'], $row['product_id'], $row['num'], $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id, $store_id);
                    }
                } else {
                    $res_goods = PackageGoods::select('goods_id', 'goods_number')
                        ->where('package_id', $row['goods_id']);
                    $res_goods = $res_goods->with('getGoods');
                    $res_goods = $this->baseRepository->getToArrayGet($res_goods);

                    if ($res_goods) {
                        foreach ($res_goods as $row_goods) {
                            $is_goods = $row_goods['get_goods'] ? $row_goods['get_goods'] : [];

                            if ($is_dec) {
                                $this->changeGoodsStorage($row_goods['goods_id'], $row['product_id'], -($row['num'] * $row_goods['goods_number']), $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id);
                            } elseif ($is_goods && $is_goods['is_real']) {
                                $this->changeGoodsStorage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']), $row['warehouse_id'], $row['area_id'], $row['area_city'], $order_id, $use_storage, $admin_id);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 商品库存增与减 货品库存增与减
     *
     * @param int $goods_id
     * @param int $product_id
     * @param int $number
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $order_id
     * @param int $use_storage
     * @param int $admin_id
     * @param int $store_id
     * @return bool
     */
    private function changeGoodsStorage($goods_id = 0, $product_id = 0, $number = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $order_id = 0, $use_storage = 0, $admin_id = 0, $store_id = 0)
    {
        if ($number == 0) {
            return true; // 值为0即不做、增减操作，返回true
        }

        if (empty($goods_id) || empty($number)) {
            return false;
        }
        $number = ($number > 0) ? '+ ' . $number : $number;

        $goods = Goods::select('model_inventory', 'model_attr')
            ->where('goods_id', $goods_id);
        $goods = $this->baseRepository->getToArrayFirst($goods);

        $goods['model_inventory'] = $goods['model_inventory'] ?? 0;
        $goods['model_attr'] = $goods['model_attr'] ?? 0;

        /* 秒杀活动扩展信息 */
        $extension_code = OrderGoods::where('order_id', $order_id)->value('extension_code');

        if ($extension_code && substr($extension_code, 0, 7) == 'seckill') {
            $is_seckill = true;
            $sec_id = substr($extension_code, 7);
        } else {
            $is_seckill = false;
        }

        /* 处理货品库存 */
        $abs_number = abs($number);
        if (!empty($product_id)) {

            if (isset($store_id) && $store_id > 0) {
                $res = StoreProducts::where('store_id', $store_id);
            } else {
                if ($goods['model_inventory'] == 1) {
                    $res = ProductsWarehouse::whereRaw(1);
                } elseif ($goods['model_inventory'] == 2) {
                    $res = ProductsArea::whereRaw(1);
                } else {
                    $res = Products::whereRaw(1);
                }
            }

            if ($is_seckill) {
                $set_update = "IF(sec_num >= $abs_number, sec_num $number, 0)";
            } elseif ($number < 0) {
                $set_update = "IF(product_number >= $abs_number, product_number $number, 0)";
            } else {
                $set_update = "product_number $number";
            }
            if ($is_seckill) {
                $other = [
                    'sec_num' => DB::raw($set_update)
                ];
                SeckillGoods::where('id', $sec_id)->update($other);
            } else {
                $other = [
                    'product_number' => DB::raw($set_update)
                ];
                $res->where('goods_id', $goods_id)
                    ->where('product_id', $product_id)
                    ->update($other);
            }
        } else {
            if ($number < 0) {
                if ($store_id > 0) {
                    $set_update = "IF(goods_number >= $abs_number, goods_number $number, 0)";
                } else {
                    if ($is_seckill) {
                        $set_update = "IF(sec_num >= $abs_number, sec_num $number, 0)";
                    } else {
                        if ($goods['model_inventory'] == 1 || $goods['model_inventory'] == 2) {
                            $set_update = "IF(region_number >= $abs_number, region_number $number, 0)";
                        } else {
                            $set_update = "IF(goods_number >= $abs_number, goods_number $number, 0)";
                        }
                    }
                }
            } else {
                if ($store_id > 0) {
                    $set_update = "goods_number $number";
                } elseif ($is_seckill) {
                    $set_update = " sec_num $number ";
                } else {
                    if ($goods['model_inventory'] == 1 || $goods['model_inventory'] == 2) {
                        $set_update = "region_number $number";
                    } else {
                        $set_update = "goods_number $number";
                    }
                }
            }

            /* 处理商品库存 */
            if ($store_id > 0) {
                $other = [
                    'goods_number' => DB::raw($set_update)
                ];
                StoreGoods::where('goods_id', $goods_id)
                    ->where('store_id', $store_id)
                    ->update($other);
            } else {
                if ($goods['model_inventory'] == 1 && !$is_seckill) {
                    $other = [
                        'region_number' => DB::raw($set_update)
                    ];
                    WarehouseGoods::where('goods_id', $goods_id)
                        ->where('region_id', $warehouse_id)
                        ->update($other);
                } elseif ($goods['model_inventory'] == 2 && !$is_seckill) {
                    $other = [
                        'region_number' => DB::raw($set_update)
                    ];
                    $update = WarehouseAreaGoods::where('goods_id', $goods_id)
                        ->where('region_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $update = $update->where('city_id', $area_city);
                    }

                    $update->update($other);
                } else {
                    if ($is_seckill) {
                        $other = [
                            'sec_num' => DB::raw($set_update)
                        ];
                        SeckillGoods::where('id', $sec_id)
                            ->update($other);
                    } else {
                        $other = [
                            'goods_number' => DB::raw($set_update)
                        ];
                        Goods::where('goods_id', $goods_id)
                            ->update($other);
                    }
                }
            }
        }

        //库存日志
        $logs_other = [
            'goods_id' => $goods_id,
            'order_id' => $order_id,
            'use_storage' => $use_storage,
            'admin_id' => $admin_id,
            'number' => $number,
            'model_inventory' => $goods['model_inventory'],
            'model_attr' => $goods['model_attr'],
            'product_id' => $product_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'add_time' => $this->timeRepository->getGmTime()
        ];

        GoodsInventoryLogs::insert($logs_other);
        return true;
    }

    /**
     * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包、优惠券设为0
     *
     * @param array $order
     * @throws \Exception
     */
    private function returnUserSurplusIntegralBonus($order = [])
    {
        /* 处理余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['surplus'] > 0) {
            $surplus = $order['money_paid'] < 0 ? $order['surplus'] + $order['money_paid'] : $order['surplus'];
            $this->logAccountChange($order['user_id'], $surplus, 0, 0, 0, sprintf(lang('admin/order.return_order_surplus'), $order['order_sn']), ACT_OTHER, 1);

            OrderInfo::where('order_id', $order['order_id'])
                ->update(['order_amount' => 0]);
        }

        if ($order['user_id'] > 0 && $order['integral'] > 0) {
            $this->logAccountChange($order['user_id'], 0, 0, 0, $order['integral'], sprintf(lang('admin/order.return_order_integral'), $order['order_sn']), ACT_OTHER, 1);
        }

        if ($order['bonus_id'] > 0) {
            $other = [
                'order_id' => 0,
                'used_time' => 0
            ];
            UserBonus::where('bonus_id', $order['bonus_id'])->update($other);
        }


        /* 退优惠券 */
        if ($order['order_id'] > 0) {
            $coupons = OrderInfo::where('order_id', $order['order_id'])->value('coupons');
            //使用了优惠券才退券
            if ($coupons) {
                // 判断当前订单是否满足了返券要求
                $other = [
                    'order_id' => 0,
                    'is_use_time' => 0,
                    'is_use' => 0
                ];
                CouponsUser::where('order_id', $order['order_id'])->update($other);
            }
        }

        /* 退储值卡 start */
        if ($order['order_id'] > 0) {
            $this->returnCardMoney($order['order_id']);
        }
        /* 退储值卡 end */

        /* 修改订单 */
        $arr = [
            'bonus_id' => 0,
            'bonus' => 0,
            'integral' => 0,
            'integral_money' => 0,
            'surplus' => 0
        ];

        OrderInfo::where('order_id', $order['order_id'])->update($arr);
    }

    /**
     * 记录帐户变动
     * @param int $user_id 用户id
     * @param int $user_money 可用余额变动[float]
     * @param int $frozen_money 冻结余额变动[float]
     * @param int $rank_points 等级积分变动
     * @param int $pay_points 消费积分变动
     * @param string $change_desc 变动说明
     * @param int $change_type 变动类型：参见常量文件
     * @param int $order_type
     * @param int $deposit_fee
     * @throws \Exception
     */
    private function logAccountChange($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER, $order_type = 0, $deposit_fee = 0)
    {
        $is_go = true;
        $is_user_money = 0;
        $is_pay_points = 0;

        //控制只有后台执行，前台不操作以下程序
        if ($change_desc && $order_type) {
            $change_desc_arr = $change_desc ? explode(" ", $change_desc) : [];

            if (count($change_desc_arr) >= 2) {
                $order_sn = !empty($change_desc_arr[1]) ? $change_desc_arr[1] : '';

                if (!empty($order_sn)) {
                    $order_res = OrderInfo::select(['order_id', 'main_order_id'])->where('order_sn', $order_sn);
                    $order_res = $this->baseRepository->getToArrayFirst($order_res);
                } else {
                    $order_res = [];
                }

                if (empty($order_res)) {
                    $is_go = false;
                }

                if ($order_res) {
                    if ($order_res['main_order_id'] > 0) {  //操作无效或取消订单时，先查询该订单是否有主订单

                        $ordor_main = OrderInfo::select('order_sn')->where('order_id', $order_res['main_order_id']);
                        $ordor_main = $this->baseRepository->getToArrayFirst($ordor_main);

                        if ($ordor_main) {
                            $order_surplus_desc = sprintf(lang('user.return_order_surplus'), $ordor_main['order_sn']);
                            $order_integral_desc = sprintf(lang('user.return_order_integral'), $ordor_main['order_sn']);
                        } else {
                            $order_surplus_desc = '';
                            $order_integral_desc = '';
                        }

                        //查询该订单的主订单是否已操作过无效或取消订单
                        $change_desc = [$order_surplus_desc, $order_integral_desc];

                        $log_res = [];
                        if ($change_desc) {
                            $log_res = AccountLog::select('log_id')->whereIn('change_desc', $change_desc);
                            $log_res = $this->baseRepository->getToArrayGet($log_res);
                        }

                        if ($log_res) {
                            $is_go = false;
                        }
                    } else {
                        if ($order_res && $order_res['order_id'] > 0) {
                            $main_order_res = OrderInfo::select('order_id', 'order_sn')->where('main_order_id', $order_res['order_id']);
                            $main_order_res = $this->baseRepository->getToArrayGet($main_order_res);

                            if ($main_order_res > 0) {
                                foreach ($main_order_res as $key => $row) {
                                    $order_surplus_desc = sprintf(lang('user.return_order_surplus'), $row['order_sn']);
                                    $order_integral_desc = sprintf(lang('user.return_order_integral'), $row['order_sn']);

                                    $main_change_desc = [$order_surplus_desc, $order_integral_desc];
                                    $parent_account_log = AccountLog::select(['user_money', 'pay_points'])->whereIn('change_desc', $main_change_desc);
                                    $parent_account_log = $this->baseRepository->getToArrayGet($parent_account_log);

                                    if ($parent_account_log) {
                                        if ($user_money) {
                                            $is_user_money += $parent_account_log[0]['user_money'];
                                        }

                                        if ($pay_points) {
                                            $is_pay_points += $parent_account_log[1]['pay_points'];
                                        }
                                    }
                                }
                            }
                        }

                        if ($user_money) {
                            $user_money -= $is_user_money;
                        }

                        if ($pay_points) {
                            $pay_points -= $is_pay_points;
                        }
                    }
                }
            }
        } /**
         * 判断是否是支付订单操作
         * 【订单号不能为空】
         *
         */
        elseif ($change_desc) {
            if (strpos($change_desc, '：') !== false) {
                $change_desc_arr = explode("：", $change_desc);
            } else {
                $change_desc_arr = explode(" ", $change_desc);
            }

            if (count($change_desc_arr) >= 2) {
                if (!empty($change_desc_arr[0]) && ($change_desc_arr[0] == '支付订单' || $change_desc_arr[0] == '追加使用余额支付订单')) {
                    if (!empty($change_desc_arr[1])) {
                        $change_desc_arr[1] = trim($change_desc_arr[1]);
                    }

                    $order_sn = !empty($change_desc_arr[1]) ? $change_desc_arr[1] : '';

                    if ($order_sn) {
                        $order_res = OrderInfo::where('order_sn', $order_sn);
                        $order_res = $this->baseRepository->getToArrayFirst($order_res);
                    } else {
                        $order_res = [];
                    }

                    if (empty($order_res)) {
                        $is_go = false;
                    }
                }
            }
        }

        if ($is_go && ($user_money || $frozen_money || $rank_points || $pay_points)) {
            if (is_array($change_desc)) {
                $change_desc = implode('<br/>', $change_desc);
            }

            /* 插入帐户变动记录 */
            $account_log = [
                'user_id' => $user_id,
                'user_money' => $user_money,
                'frozen_money' => $frozen_money,
                'rank_points' => $rank_points,
                'pay_points' => $pay_points,
                'change_time' => $this->timeRepository->getGmTime(),
                'change_desc' => $change_desc,
                'change_type' => $change_type,
                'deposit_fee' => $deposit_fee
            ];

            AccountLog::insert($account_log);

            /* 更新用户信息 */
            $user_money = $user_money + $deposit_fee;
            $update_log = [
                'frozen_money' => DB::raw("frozen_money  + ('$frozen_money')"),
                'pay_points' => DB::raw("pay_points  + ('$pay_points')"),
                'rank_points' => DB::raw("rank_points  + ('$rank_points')")
            ];

            Users::where('user_id', $user_id)->increment('user_money', $user_money, $update_log);

            if (!$this->userRankService->judgeUserSpecialRank($user_id)) {

                /* 更新会员当前等级 start */
                $user_rank_points = Users::where('user_id', $user_id)->value('rank_points');
                $user_rank_points = $user_rank_points ? $user_rank_points : 0;

                $rank_row = [];
                if ($user_rank_points >= 0) {
                    //1.4.3 会员等级修改（成长值只有下限）
                    $rank_row = $this->userRankService->getUserRankByPoint($user_rank_points);
                }

                if ($rank_row) {
                    $rank_row['discount'] = $rank_row['discount'] / 100;
                } else {
                    $rank_row['discount'] = 1;
                    $rank_row['rank_id'] = 0;
                }

                /* 更新会员当前等级 end */
                Users::where('user_id', $user_id)->update([
                    'user_rank' => $rank_row['rank_id']
                ]);
            }
        }
    }

    /**
     * 退还订单使用的储值卡消费金额
     *
     * @param int $order_id
     * @param int $ret_id
     * @param string $return_sn
     * @throws \Exception
     */
    private function returnCardMoney($order_id = 0, $ret_id = 0, $return_sn = '')
    {
        $row = ValueCardRecord::where('order_id', $order_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        if ($row) {

            $order_info = OrderInfo::where('order_id', $order_id);
            $order_info = $this->baseRepository->getToArrayFirst($order_info);

            /* 更新储值卡金额 */
            ValueCard::where('vid', $row['vc_id'])->increment('card_money', $row['use_val']);

            /* 更新储值卡金额使用日志 */
            $log = [
                'vc_id' => $row['vc_id'],
                'order_id' => $order_id,
                'use_val' => $row['use_val'],
                'vc_dis' => 1,
                'add_val' => $row['use_val'],
                'record_time' => $this->timeRepository->getGmTime()
            ];

            ValueCardRecord::insert($log);

            if ($return_sn) {
                $return_note = sprintf(lang('user.order_vcard_return'), $row['use_val']);
                $this->returnAction($ret_id, RF_AGREE_APPLY, FF_REFOUND, $return_note);

                $return_sn = "<br/>" . lang('order.order_return_running_number') . "：" . $return_sn;
            }

            $note = sprintf(lang('user.order_vcard_return') . $return_sn, $row['use_val']);
            $this->orderAction($order_info['order_sn'], $order_info['order_status'], $order_info['shipping_status'], $order_info['pay_status'], $note);
        }
    }

    /**
     * 记录订单操作记录
     *
     * @param $ret_id
     * @param $return_status
     * @param $refound_status
     * @param string $note
     */
    private function returnAction($ret_id, $return_status, $refound_status, $note = '')
    {
        if ($ret_id) {
            $other = [
                'ret_id' => $ret_id,
                'return_status' => $return_status,
                'refound_status' => $refound_status,
                'action_note' => $note,
                'log_time' => $this->timeRepository->getGmTime()
            ];
            ReturnAction::insert($other);
        }
    }
}
