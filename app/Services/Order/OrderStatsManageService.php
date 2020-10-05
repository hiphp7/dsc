<?php

namespace App\Services\Order;

use App\Models\OrderInfo;
use App\Models\Payment;
use App\Models\Shipping;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class OrderStatsManageService
{
    protected $baseRepository;
    protected $orderService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->orderService = $orderService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /*------------------------------------------------------ */
    //--订单统计需要的函数
    /*------------------------------------------------------ */
    /**
     * 取得订单概况数据(包括订单的几种状态)
     * @param       $start_date    开始查询的日期
     * @param       $end_date      查询的结束日期
     * @param       $type          查询类型：0为订单数量，1为销售额
     * @return      $order_info    订单概况数据
     */
    public function getOrderinfo($start_date, $end_date, $type = 0)
    {
        $order_info = [];
        $adminru = get_admin_ru_id();

        if ($type == 1) {
            /* 未确认订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::selectRaw('IFNULL(SUM(' . $this->orderService->orderAmountField('') . '), 0) as unconfirmed_num')
                ->where('order_status', OS_UNCONFIRMED)
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['unconfirmed_num'] = $res->value('unconfirmed_num');

            /* 已确认订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::selectRaw('IFNULL(SUM(' . $this->orderService->orderAmountField('') . '), 0) as confirmed_num')
                ->where('order_status', OS_CONFIRMED)
                ->whereNotIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                ->whereNotIn('pay_status', [PS_PAYED, PS_PAYING])
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['confirmed_num'] = $res->value('confirmed_num');

            /* 已成交订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::selectRaw('IFNULL(SUM(' . $this->orderService->orderAmountField('') . '), 0) as succeed_num')
                ->whereRaw('1' . $this->orderService->orderQuerySql('finished'))
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['succeed_num'] = $res->value('succeed_num');

            /* 无效或已取消订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::selectRaw('IFNULL(SUM(' . $this->orderService->orderAmountField('') . '), 0) as invalid_num')
                ->whereIn('order_status', [OS_CANCELED, OS_INVALID])
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['invalid_num'] = $res->value('invalid_num');
        } else {
            /* 未确认订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::where('order_status', OS_UNCONFIRMED)
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }
            $order_info['unconfirmed_num'] = $res->count();

            /* 已确认订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::where('order_status', OS_CONFIRMED)
                ->whereNotIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                ->whereNotIn('pay_status', [PS_PAYED, PS_PAYING])
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['confirmed_num'] = $res->count();

            /* 已成交订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::whereRaw('1' . $this->orderService->orderQuerySql('finished'))
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['succeed_num'] = $res->count();

            /* 无效或已取消订单数 */

            //主订单下有子订单时，则主订单不显示
            $res = OrderInfo::whereIn('order_status', [OS_CANCELED, OS_INVALID])
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<', ($end_date + 86400))
                ->where('main_count', 0);

            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $order_info['invalid_num'] = $res->count();
        }

        return $order_info;
    }

    public function getOrderGeneral($type = 0)
    {
        $adminru = get_admin_ru_id();

        /* 计算订单各种费用之和的语句 */
        $total_fee = "SUM(" . $this->orderService->orderAmountField('') . ") AS total_turnover ";

        //主订单下有子订单时，则主订单不显示;
        $res = OrderInfo::selectRaw("count(*) as total_order_num," . $total_fee)
            ->whereRaw('1' . $this->orderService->orderQuerySql('finished', ''))
            ->where('main_count', 0);

        if ($type == 1) {
            //卖场 start
            if ($adminru['rs_id'] > 0) {
                $res = $res->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $res = $this->dscRepository->getWhereRsid($res, 'ru_id', $adminru['rs_id']);
            } else {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }
            //卖场 end
        }

        $res = $this->baseRepository->getToArrayFirst($res);
        return $res;
    }

    //multi--One start
    public function getPayTypeTop($start_date, $ru_id, $type = 0)
    {

        //当月的开始日期和结束日期
        $day = $this->timeRepository->getLocalDate('Y-m-d', $start_date);
        $time = $this->getthemonth($day);

        $start_date = $this->timeRepository->getLocalStrtoTime($time[0]);
        $end_date = $this->timeRepository->getLocalStrtoTime($time[1]);

        $res = $this->getPayType($start_date, $end_date, $ru_id, $type);

        return $res;
    }

    public function getShippingTypeTop($start_date, $ru_id, $type = 0)
    {

        //当月的开始日期和结束日期
        $day = $this->timeRepository->getLocalDate('Y-m-d', $start_date);
        $time = $this->getthemonth($day);

        $start_date = $this->timeRepository->getLocalStrtoTime($time[0]);
        $end_date = $this->timeRepository->getLocalStrtoTime($time[1]);

        $res = $this->getShippingType($start_date, $end_date, $ru_id, $type);

        return $res;
    }
    //multi--One end

    //multi--Two start
    public function getPayType($start_date, $end_date, $ru_id)
    {
        $adminru = get_admin_ru_id();

        //主订单下有子订单时，则主订单不显示;
        $rs_id = $adminru['rs_id'];
        $res = Payment::whereHas('getOrder', function ($query) use ($start_date, $end_date, $rs_id, $ru_id) {
            $query = $query->whereRaw('1' . $this->orderService->orderQuerySql('finished'))
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<=', $end_date)
                ->where('main_count', 0);

            //卖场 start
            if ($rs_id > 0) {
                $query = $query->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $query = $this->dscRepository->getWhereRsid($query, 'ru_id', $rs_id);
            } else {
                $query = $query->where('ru_id', $ru_id);
            }

            $query->orderBy('add_time', 'DESC');
            //卖场 end
        });

        $array = $this->baseRepository->getToArrayGet($res);
        if ($array) {
            foreach ($array as $key => $value) {
                $value['pay_id'] = 0;
                $value['pay_time'] = 0;
                if (isset($value['get_order']) && !empty($value['get_order'])) {
                    $value['pay_id'] = $value['get_order']['pay_id'];
                    $value['pay_time'] = $value['get_order']['pay_time'];
                }
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public function getShippingType($start_date, $end_date, $ru_id)
    {
        //主订单下有子订单时，则主订单不显示;
        $adminru = get_admin_ru_id();

        //主订单下有子订单时，则主订单不显示;
        $rs_id = $adminru['rs_id'];
        $res = Shipping::whereHas('getOrder', function ($query) use ($start_date, $end_date, $rs_id, $ru_id) {
            $query = $query->whereRaw('1' . $this->orderService->orderQuerySql('finished'))
                ->where('add_time', '>=', $start_date)
                ->where('add_time', '<=', $end_date)
                ->where('main_count', 0);

            //卖场 start
            if ($rs_id > 0) {
                $query = $query->where(function ($query) {
                    $query->whereHas('getOrderGoods');
                });
                $query = $this->dscRepository->getWhereRsid($query, 'ru_id', $rs_id);
            } else {
                $query = $query->where('ru_id', $ru_id);
            }

            $query->orderBy('add_time', 'DESC');
            //卖场 end
        });

        $array = $this->baseRepository->getToArrayGet($res);
        if ($array) {
            foreach ($array as $key => $value) {
                $value['ship_name'] = $value['shipping_name'];
                $value['shipping_time'] = 0;
                if (isset($value['get_order']) && !empty($value['get_order'])) {
                    $value['shipping_time'] = $value['get_order']['shipping_time'];
                }
                $array[$key] = $value;
            }
        }

        return $array;
    }
    //multi--Two end

    //转为二维数组
    public function getToArray($arr1, $arr2, $str1 = '', $str2 = '', $str3 = '', $str4 = '')
    {
        $ship_arr = [];
        foreach ($arr1 as $key1 => $row1) {
            foreach ($arr2 as $key2 => $row2) {
                if ($row1["{$str1}"] == $row2["{$str1}"]) {
                    $ship_arr[$row1["{$str1}"]]["{$str2}"][$key2] = $row2;
                    $ship_arr[$row1["{$str1}"]]["{$str3}"] = $row1["{$str3}"];
                    if (!empty($str4)) {
                        $ship_arr[$row1["{$str1}"]]["{$str4}"] = $row1["{$str4}"];
                    }
                }
            }
        }

        return $ship_arr;
    }

    public function getthemonth($date)
    {
        $firstday = $this->timeRepository->getLocalDate('Y-m-01', $this->timeRepository->getLocalStrtoTime($date));
        $lastday = $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime("$firstday +1 month -1 day"));
        return [$firstday, $lastday];
    }


    public function getArea($countries, $pro = 0)
    {
        $res = OrderInfo::where('main_count', 0)
            ->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
            ->where('shipping_status', SS_RECEIVED)
            ->whereIn('pay_status', [PS_PAYED, PS_PAYING]);

        //主订单下有子订单时，则主订单不显示
        $array = [];
        if ($countries == 1 && $pro == 0) {
            $res = $res->where('country', $countries);
            $res = $res->with(['getRegionProvince']);
            $res = $res->selectRaw('COUNT(*) AS area_num,province')->groupBy('province');
            $array = $this->baseRepository->getToArrayGet($res);

            if (!empty($array)) {
                foreach ($array as $key => $value) {
                    $value['region_name'] = '';
                    if (isset($value['get_region_province']) && !empty($value['get_region_province'])) {
                        $value['region_name'] = $value['get_region_province']['region_name'];
                    }
                    $array[$key] = $value;
                }
            }
        } elseif ($countries == 1 && $pro > 0) {
            $res = $res->where('province', $pro);
            $res = $res->with(['getRegionCity']);
            $res = $res->selectRaw('COUNT(*) AS area_num,province')->groupBy('city');

            $array = $this->baseRepository->getToArrayGet($res);
            if (!empty($array)) {
                foreach ($array as $key => $value) {
                    $value['region_name'] = '';
                    if (isset($value['get_region_city']) && !empty($value['get_region_city'])) {
                        $value['region_name'] = $value['get_region_city']['region_name'];
                    }
                    $array[$key] = $value;
                }
            }
        }

        return $array;
    }
}
