<?php

namespace App\Models;

use App\Entities\AuctionLog as Base;

/**
 * Class AuctionLog
 */
class AuctionLog extends Base
{
    /**
     * 关联会员
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'bid_user');
    }

    /**
     * 关联活动订单条件查询
     *
     * @access  public
     * @objet  $order
     * @return  array
     */
    public function scopeSearchKeyword($query, $auction = [])
    {
        if (isset($auction->keyword)) {
            if ($auction->type == 'dateTime' || $auction->type == 'order_status' || $auction->type == 'is_going' || $auction->type == 'is_finished') {
                $date_keyword = '';
                if ($auction->idTxt == 'submitDate') {
                    $date_keyword = $auction->keyword;
                    $status_keyword = $auction->status_keyword;
                } elseif ($auction->idTxt == 'status_list') {
                    $date_keyword = $auction->date_keyword;
                    $status_keyword = $auction->keyword;
                } elseif ($auction->idTxt == 'is_going' || $auction->idTxt == 'is_finished') {
                    $status_keyword = $auction->keyword;
                }

                $firstSecToday = $this->getLocalMktime(0, 0, 0, date("m"), date("d"), date("Y")); //当天开始返回时间戳 比如1369814400 2013-05-30 00:00:00
                $lastSecToday = $this->getLocalMktime(0, 0, 0, date("m"), date("d") + 1, date("Y")) - 1; //当天结束返回时间戳 比如1369900799  2013-05-30 00:00:00

                if ($date_keyword && $date_keyword == 'today') {
                    $query = $query->where('bid_time', '>=', $firstSecToday)->where('bid_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'three_today') {
                    $firstSecToday = $firstSecToday - 24 * 3600 * 2;
                    $query = $query->where('bid_time', '>=', $firstSecToday)->where('bid_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'aweek') {
                    $firstSecToday = $firstSecToday - 24 * 3600 * 6;
                    $query = $query->where('bid_time', '>=', $firstSecToday)->where('bid_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'thismonth') {
                    $first_month_day = $this->getLocalMktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
                    $last_month_day = $this->getLocalMktime(0, 0, 0, date('m'), date('t'), date('Y')) - 1; //本月最后一天

                    $query = $query->where('bid_time', '>=', $first_month_day)->where('bid_time', '<=', $last_month_day);
                }
            }
        }

        return $query;
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
