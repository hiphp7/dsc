<?php

namespace App\Models;

use App\Entities\WholesaleOrderInfo as Base;

/**
 * Class WholesaleOrderInfo
 */
class WholesaleOrderInfo extends Base
{
    /**
     * 关联订单商品
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getWholesaleOrderGoods()
    {
        return $this->hasOne('App\Models\WholesaleOrderGoods', 'order_id', 'order_id');
    }

    /**
     * 关联订单主订单
     *
     * @access  public
     * @param main_order_id
     * @return  array
     */
    public function getMainOrderId()
    {
        return $this->hasOne('App\Models\WholesaleOrderInfo', 'main_order_id', 'order_id');
    }

    /**
     * 获取主订单ID
     *
     * @access  public
     * @param getMainOrderId
     * @return  Number
     */
    public function scopeMainOrderCount()
    {
        return $this->whereHas('getMainOrderId', function ($query) {
            $query->selectRaw("count(*) as count")->Having('count', 0);
        });
    }

    /**
     * 关联订单条件查询
     *
     * @access  public
     * @objet  $order
     * @return  array
     */
    public function scopeSearchKeyword($query, $order = [])
    {
        if (isset($order->keyword)) {
            if ($order->type == 'dateTime' || $order->type == 'order_status') {
                $date_keyword = '';
                if ($order->idTxt == 'wholesale_submitDate') { //订单时间范围
                    $date_keyword = $order->keyword;
                    $status_keyword = -1;
                } elseif ($order->idTxt == 'wholesale_status_list') { //订单状态
                    $date_keyword = $order->date_keyword;
                    $status_keyword = $order->keyword;
                }

                $firstSecToday = $this->getLocalMktime(0, 0, 0, date("m"), date("d"), date("Y")); //当天开始返回时间戳 比如1369814400 2013-05-30 00:00:00
                $lastSecToday = $this->getLocalMktime(0, 0, 0, date("m"), date("d") + 1, date("Y")) - 1; //当天结束返回时间戳 比如1369900799  2013-05-30 00:00:00

                if ($date_keyword && $date_keyword == 'today') {
                    $query->where('add_time', '>=', $firstSecToday)
                        ->where('add_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'three_today') {
                    $firstSecToday = $firstSecToday - 24 * 3600 * 2;

                    $query->where('add_time', '>=', $firstSecToday)
                        ->where('add_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'aweek') {
                    $firstSecToday = $firstSecToday - 24 * 3600 * 6;

                    $query->where('add_time', '>=', $firstSecToday)
                        ->where('add_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'thismonth') {
                    $first_month_day = strtotime("-1 month"); //上个月的今天
                    $last_month_day = $this->getGmtime(); //今天

                    $query->where('add_time', '>=', $first_month_day)
                        ->where('add_time', '<=', $last_month_day);
                }

                //综合状态
                switch ($status_keyword) {
                    case 0:
                        $query = $query->where('order_status', 0);
                        break;

                    case 1:
                        $query = $query->where('order_status', 1);
                        break;
                }
            }
        }

        return $query;
    }

    public function getRegionCountry()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'country');
    }

    /**
     * 关联省份
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getRegionProvince()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'province');
    }

    /**
     * 关联城市
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getRegionCity()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'city');
    }

    /**
     * 关联城镇
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getRegionDistrict()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'district');
    }

    /**
     * 关联乡村/街道
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }

    /**
     * 关联会员
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUser()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联供货商
     *
     * @access  public
     * @param suppliers_id
     * @return  array
     */
    public function getSuppliers()
    {
        return $this->hasOne('App\Models\Suppliers', 'suppliers_id', 'suppliers_id');
    }

    /**
     * 关联支付方式
     *
     * @access  public
     * @param pay_id
     * @return  array
     */
    public function getPayment()
    {
        return $this->hasOne('App\Models\Payment', 'pay_id', 'pay_id');
    }

    /**
     * 关联订单发货单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getWholesaleDeliveryOrder()
    {
        return $this->hasOne('App\Models\WholesaleDeliveryOrder', 'order_id', 'order_id');
    }

    /**
     *  生成一个用户自定义时区日期的GMT时间戳
     *
     * @access  public
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @param int $month
     * @param int $day
     * @param int $year
     *
     * @return void
     */
    private function getLocalMktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null)
    {
        $timezone = session()->has('timezone') ? session('timezone') : $GLOBALS['_CFG']['timezone'];

        /**
         * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
         * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
         * */
        $time = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;

        return $time;
    }
}
