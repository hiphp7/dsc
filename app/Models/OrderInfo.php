<?php

namespace App\Models;

use App\Entities\OrderInfo as Base;

/**
 * Class OrderInfo
 */
class OrderInfo extends Base
{

    /**
     * 关联订单商品列表
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function goods()
    {
        return $this->hasMany('App\Models\OrderGoods', 'order_id', 'order_id');
    }

    /**
     * 关联退换货订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrderReturn()
    {
        return $this->hasOne('App\Models\OrderReturn', 'order_id', 'order_id');
    }

    /**
     * 关联订单发货单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getDeliveryOrder()
    {
        return $this->hasOne('App\Models\DeliveryOrder', 'order_id', 'order_id');
    }

    /**
     * 关联账单订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getSellerBillOrder()
    {
        return $this->hasOne('App\Models\SellerBillOrder', 'order_id', 'order_id');
    }

    /**
     * 关联订单商品
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrderGoods()
    {
        return $this->hasOne('App\Models\OrderGoods', 'order_id', 'order_id');
    }

    /**
     * 关联订单商品列表
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrderGoodsList()
    {
        return $this->hasMany('App\Models\OrderGoods', 'order_id', 'order_id');
    }

    /**
     * 关联订单会员
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
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
     * 关联订单记录
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getOrderAction()
    {
        return $this->hasOne('App\Models\OrderAction', 'order_id', 'order_id');
    }

    /**
     * 关联订单记录
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function scopeOrderActionWait($query, $where, $type = 0)
    {
        $where['type'] = $type;
        return $query->selectRaw('count(*) as count')->whereHas('getOrderAction', function ($query) use ($where) {
            $query = $query->selectRaw("count(*) as count");

            if (isset($where['action_wait_order']['order_status'])) {
                if (isset($where['action_wait_order']['order_status']) && is_array($where['action_wait_order']['order_status'])) {
                    $query = $query->whereIn('order_status', $where['action_wait_order']['order_status']);
                } else {
                    $query = $query->where('order_status', $where['action_wait_order']['order_status']);
                }

                if (isset($where['action_wait_order']['shipping_status']) && is_array($where['action_wait_order']['shipping_status'])) {
                    $query = $query->whereIn('shipping_status', $where['action_wait_order']['shipping_status']);
                } else {
                    $query = $query->where('shipping_status', $where['action_wait_order']['shipping_status']);
                }

                if (isset($where['action_wait_order']['pay_status'])) {
                    if (is_array($where['action_wait_order']['pay_status'])) {
                        $query->whereIn('pay_status', $where['action_wait_order']['pay_status']);
                    } else {
                        $query->where('pay_status', $where['action_wait_order']['pay_status']);
                    }
                }
            }

            if ($where['type'] == 1) {
                $this->Having('count', '>', 0);
            } else {
                $this->Having('count', 0);
            }
        })->value('count');
    }

    /**
     * 关联订单记录
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function scopeOrderActionWaitTime($query, $where)
    {
        return $query->selectRaw('count(*) as count')->whereHas('getOrderAction', function ($query) use ($where) {
            $query->selectRaw("count(*) as count")
                ->where('log_time', '>=', $where['start_time'])
                ->where('log_time', '<=', $where['end_time'])
                ->Having('count', '>', 0);
        })->value('count');
    }

    /**
     * 关联订单会员分成订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getAffiliateLog()
    {
        return $this->hasOne('App\Models\AffiliateLog', 'order_id', 'order_id');
    }

    /**
     * 关联白条订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getBaitiaoLog()
    {
        return $this->hasOne('App\Models\BaitiaoLog', 'order_id', 'order_id');
    }

    /**
     * 关联白条订单ID
     *
     * @access  public
     * @param getBaitiaoLog
     * @return  Number
     */
    public function scopeBaitiaoLogCount()
    {
        return $this->whereHas('getBaitiaoLog', function ($query) {
            $query->selectRaw("count(*) as count")->Having('count', '>', 0);
        });
    }

    /**
     * 关联门店订单
     *
     * @access  public
     * @param order_id
     * @return  array
     */
    public function getStoreOrder()
    {
        return $this->hasOne('App\Models\StoreOrder', 'order_id', 'order_id');
    }

    /**
     * 关联门店订单ID
     *
     * @access  public
     * @param getStoreOrder
     * @return  Number
     */
    public function scopeStoreOrderCount()
    {
        return $this->whereHas('getStoreOrder', function ($query) {
            $query->selectRaw("count(*) as count")->Having('count', '>', 0);
        });
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
        return $this->hasOne('App\Models\OrderInfo', 'main_order_id', 'order_id');
    }

    /**
     * 关联订单主订单子订单列表
     *
     * @access  public
     * @param main_order_id
     * @return  array
     */
    public function getMainOrderChild()
    {
        return $this->hasMany('App\Models\OrderInfo', 'main_order_id', 'order_id');
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
        $order->idTxt = $order->idTxt ?? '';
        $condition = $order->idTxt == 'signNum' ? true : false;
        if (isset($order->keyword)) {
            if ($order->type == 'dateTime' || $order->type == 'order_status' || $order->type == 'toBe_confirmed' || $order->type == 'toBe_finished' || $order->type == 'toBe_pay' || $order->type == 'toBe_unconfirmed' || $condition) {
                $date_keyword = '';
                if ($order->idTxt == 'submitDate') { //订单时间范围
                    $date_keyword = $order->keyword;
                    $status_keyword = $order->status_keyword;
                } elseif ($order->idTxt == 'status_list') { //订单状态
                    $date_keyword = $order->date_keyword;
                    $status_keyword = $order->keyword;
                } elseif ($order->idTxt == 'payId' || $order->idTxt == 'to_finished' || $order->idTxt == 'to_confirm_order' || $order->idTxt == 'to_unconfirmed' || $condition) {
                    $status_keyword = $order->keyword;
                }

                $firstSecToday = $this->getLocalMktime(0, 0, 0, date("m"), date("d"), date("Y")); //当天开始返回时间戳 比如1369814400 2013-05-30 00:00:00
                $lastSecToday = $this->getLocalMktime(0, 0, 0, date("m"), date("d") + 1, date("Y")) - 1; //当天结束返回时间戳 比如1369900799  2013-05-30 00:00:00

                if ($date_keyword && $date_keyword == 'today') {
                    $query = $query->where('add_time', '>=', $firstSecToday)
                        ->where('add_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'three_today') {
                    $firstSecToday = $firstSecToday - 24 * 3600 * 2;

                    $query = $query->where('add_time', '>=', $firstSecToday)
                        ->where('add_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'aweek') {
                    $firstSecToday = $firstSecToday - 24 * 3600 * 6;

                    $query = $query->where('add_time', '>=', $firstSecToday)
                        ->where('add_time', '<=', $lastSecToday);
                } elseif ($date_keyword && $date_keyword == 'thismonth') {
                    $first_month_day = strtotime("-1 month"); //上个月的今天
                    $last_month_day = $this->getGmtime(); //今天

                    $query = $query->where('add_time', '>=', $first_month_day)
                        ->where('add_time', '<=', $last_month_day);
                }

                //综合状态
                switch ($status_keyword) {
                    case CS_AWAIT_PAY:
                        $query = $query->getOrderQuerySql('await_pay');
                        break;

                    case CS_AWAIT_SHIP:
                        $query = $query->getOrderQuerySql('await_ship');
                        break;

                    case CS_FINISHED:
                        $query = $query->getOrderQuerySql('finished');
                        break;

                    case CS_TO_CONFIRM:
                        $query = $query->getOrderQuerySql('to_confirm');
                        break;

                    case OS_UNCONFIRMED:
                        $query = $query->getOrderQuerySql('unconfirmed');
                        break;

                    case PS_PAYING:
                        if ($status_keyword != -1) {
                            $query = $query->where('pay_status', $status_keyword);
                        }
                        break;

                    case OS_SHIPPED_PART:
                        if ($status_keyword != -1) {
                            $query = $query->where('shipping_status', $status_keyword - 2);
                        }
                        break;

                    default:
                        if ($status_keyword != -1) {
                            $query = $query->where('order_status', $status_keyword);
                        }
                }
            }
        }

        return $query;
    }

    /**
     * 生成一个用户自定义时区日期的GMT时间戳
     *
     * @param null $hour
     * @param null $minute
     * @param null $second
     * @param null $month
     * @param null $day
     * @param null $year
     * @return false|float|int
     * @throws \Exception
     */
    private function getLocalMktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null)
    {
        $shopConfig = cache('shop_config');
        $shopConfig = !is_null($shopConfig) ? $shopConfig : [];

        $timezone = session()->has('timezone') ? session('timezone') : $shopConfig['timezone'] ?? 8;

        /**
         * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
         * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
         * */
        $time = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;

        return $time;
    }

    /**
     * 获得当前格林威治时间的时间戳
     *
     * @return  integer
     */
    private function getGmtime()
    {
        return (time() - date('Z'));
    }

    /**
     * 生成查询订单的sql
     * @param string $type 类型
     * @param string $alias order表的别名（包括.例如 o.）
     * @return  string
     */
    public function scopeGetOrderQuerySql($query, $type = 'finished')
    {

        /* 已完成订单：已确认订单、已付款、已发货（用户已确认收货） */
        if ($type == 'finished') {
            return $query->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
                ->whereIn('shipping_status', [SS_RECEIVED])
                ->whereIn('pay_status', [PS_PAYED, PS_PAYING]);
        } /* 待发货订单 */
        elseif ($type == 'await_ship') {
            $pay_id = $this->getPaymentIdList(true);

            return $query->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                ->whereIn('shipping_status', [SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING])
                ->where(function ($query) use ($pay_id) {
                    $query->whereIn('pay_status', [PS_PAYED, PS_PAYING])
                        ->orWhereIn('pay_id', [$pay_id]);
                });
        } /* 待付款订单 */
        elseif ($type == 'await_pay') {
            $pay_id = $this->getPaymentIdList(false);

            return $query->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED])
                ->whereIn('pay_status', [PS_UNPAYED, PS_PAYED_PART])
                ->where(function ($query) use ($pay_id) {
                    $query->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED])
                        ->orWhereIn('pay_id', $pay_id);
                });
        } /* 未确认订单 */
        elseif ($type == 'unconfirmed') {
            return $query->where('order_status', OS_UNCONFIRMED);
        } /* 未处理订单：用户可操作 */
        elseif ($type == 'unprocessed') {
            return $query->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED])
                ->where('shipping_status', SS_UNSHIPPED)
                ->where('pay_status', PS_UNPAYED);
        } /* 未付款未发货订单：管理员可操作 */
        elseif ($type == 'unpay_unship') {
            return $query->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED])
                ->whereIn('shipping_status', [SS_UNSHIPPED, SS_PREPARING])
                ->where('pay_status', PS_UNPAYED);
        } /* 已发货订单：不论是否付款 */
        elseif ($type == 'shipped') {
            return $query->where('order_status', OS_CONFIRMED)
                ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED]);
        } /* 待确认收货：已确认订单、已付款、已发货（待用户确认收货）部分发货 */
        elseif ($type == 'to_confirm') {
            return $query->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])
                ->whereIn('shipping_status', [SS_SHIPPED, SS_UNSHIPPED, SS_SHIPPED_PART])
                ->whereIn('pay_status', [PS_PAYED, PS_PAYING]);
        }
    }

    /**
     * 取得支付方式id列表
     * @param bool $is_cod 是否货到付款
     * @return  array
     */
    private function getPaymentIdList($is_cod)
    {
        $row = Payment::select('pay_id')
            ->whereRaw(1);

        if ($is_cod) {
            $row = $row->where('is_cod', 1);
        } else {
            $row = $row->where('is_cod', 0);
        }

        $row = $row->get();
        $row = $row ? $row->toArray() : [];

        if ($row) {
            $row = collect($row)->pluck('pay_id')->all();
            $row = array_unique($row);
            $row = array_values($row);
        }

        return $row;
    }

    /**
     * 关联订单投诉信息
     *
     * @access  public
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getComplaint()
    {
        return $this->hasOne('App\Models\Complaint', 'order_id', 'order_id');
    }

    /**
     * 获取订单投诉数量
     *
     * @access  public
     * @param getMainOrderId
     * @return  Number
     */
    public function scopeComplaintCount($query, $val)
    {
        if ($val == 1) {
            return $query->whereHas('getComplaint', function ($query) {
                $query->selectRaw("count(*) as count")->Having('count', '>', 0);
            });
        } else {
            return $query->whereHas('getComplaint', function ($query) {
                $query->selectRaw("count(*) as count")->Having('count', 0);
            });
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionCountry()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'country');
    }

    /**
     * 关联省份
     *
     * @access  public
     * @param user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRegionStreet()
    {
        return $this->hasOne('App\Models\Region', 'region_id', 'street');
    }

    /**
     * 关联分销会员分销记录(单条)
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getDrpLog()
    {
        return $this->hasOne('App\Models\DrpLog', 'order_id', 'order_id');
    }

    /**
     *  关联分销会员分销记录(多条)
     *
     * @access  public
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getDrpLogs()
    {
        return $this->hasMany('App\Models\DrpLog', 'order_id', 'order_id');
    }

    /**
     * 关联众筹商品
     *
     * @access  public
     * @param id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getZcGoods()
    {
        return $this->hasOne('App\Models\ZcGoods', 'id', 'zc_goods_id');
    }

    /**
     * 关联拼团
     *
     * @access  public
     * @param team_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getTeamLog()
    {
        return $this->hasOne('App\Models\TeamLog', 'team_id', 'team_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }

    public function getValueCardRecord()
    {
        return $this->hasOne('App\Models\ValueCardRecord', 'order_id', 'order_id');
    }

    public function getSellerNegativeOrder()
    {
        return $this->hasOne('App\Models\SellerNegativeOrder', 'order_id', 'order_id');
    }

    /**
     * 关联购买权益卡订单
     *
     * @param order_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderInfoMembershipCard()
    {
        return $this->hasOne('App\Models\OrderInfoMembershipCard', 'order_id', 'order_id');
    }

    /**
     * 会员订单列表显示统一条件
     *
     * @return mixed
     */
    public function scopeOrderSelectCondition()
    {
        return $this->where(function ($query) {
            $query->whereRaw("IF(pay_status < " . PS_PAYED . ", IF(child_show > 0, main_order_id > 0 AND main_count = 0, main_order_id = 0 AND main_count = 0), main_count = 0)")
                ->orWhere(function ($query) {
                    $query->where('main_count', '>', 0)
                        ->where('main_pay', 1)
                        ->where('pay_status', '<', PS_PAYED);
                });
        });
    }
}
