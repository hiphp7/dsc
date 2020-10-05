<?php

namespace App\Services\Order;

use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\UserOrderNum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Store\StoreService;

/**
 * 会员订单API
 * Class Order
 * @package App\Services
 */
class OrderApiService
{
    protected $config;
    protected $timeRepository;
    protected $storeService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        StoreService $storeService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->storeService = $storeService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 根据用户ID查询待同意状态退换货申请数量
     * @param $uid
     * @return mixed
     */
    public function getUserOrdersReturnCount($uid)
    {
        $count = OrderReturn::where('user_id', $uid)->where('refound_status', 0);

        // 检测商品是否存在
        $count = $count->whereHas('getGoods', function ($query) {
            $query->select('goods_id')->where('goods_id', '>', 0);
        });

        return $count->count();
    }

    /**
     * 根据用户ID查询订单
     *
     * @param $uid
     * @param int $status
     * @param string $type
     * @param int $page
     * @param int $size
     * @return array|\stdClass
     */
    public function getUserOrders($uid, $status = 0, $type = '', $page = 1, $size = 10)
    {
        $model = OrderInfo::orderSelectCondition();

        $model = $model->where('user_id', $uid)
            ->where('is_zc_order', 0); //排除众筹订单

        if ($status == 1) {
            // 待付款
            $order = new \stdClass;
            $order->type = 'toBe_pay';
            $order->idTxt = 'payId';
            $order->keyword = CS_AWAIT_PAY;
            $model = $model->searchKeyword($order);
        } elseif ($status == 2) {
            // 待收货
            $order = new \stdClass;
            $order->type = 'toBe_confirmed';
            $order->idTxt = 'to_confirm_order';
            $order->keyword = CS_TO_CONFIRM;
            $model = $model->searchKeyword($order);
        } elseif ($status == 3) {
            // 待收货
            $order = new \stdClass;
            $order->type = 'toBe_finished';
            $order->idTxt = 'to_finished';
            $order->keyword = CS_FINISHED;
            $model = $model->searchKeyword($order);
        }

        if ($status == 4) {
            //回收站订单
            $model = $model->where('is_delete', 1);
        } else {
            //待收货订单兼容货到付款
            if ($status == 2) {
                $cod = Payment::where('pay_code', 'cod')->where('enabled', 1)->value('pay_id');
                $data['cod'] = $cod;
                $data['user_id'] = $uid;
                if ($cod) {
                    $model = $model->orWhere(function ($query) use ($data) {
                        if ($data['cod']) {
                            $query->where('pay_id', $data['cod']);
                        }

                        if ($data['user_id']) {
                            $query->where('user_id', $data['user_id']);
                        }

                        $query->where('pay_status', 0);
                    });
                }
            }

            $model = $model->where('is_delete', 0);
        }

        //普通订单
        if (empty($type)) {
            $model = $model->where('extension_code', '');
        }
        //订单类型
        if (!empty($type)) {
            switch ($type) {
                case 'bargain':
                    $model = $model->where('extension_code', 'bargain_buy');  //砍价订单
                    break;
                case 'team':
                    $model = $model->where('extension_code', 'team_buy');    //拼团订单
                    break;
            }
        }

        $model = $model->with([
            'getOrderGoodsList' => function ($query) {
                $query->with('getGoods');
            }
        ]);

        $model = $model->withCount('getOrderReturn as is_return');

        $start = ($page - 1) * $size;

        $order = $model->offset($start)
            ->limit($size)
            ->orderBy('add_time', 'DESC')
            ->get();

        $order = $order ? $order->toArray() : [];

        return $order;
    }

    /**
     * 订单数量
     * @param $uid
     * @param int $status
     * @return mixed
     */
    public function getOrderCount($uid, $status = 0)
    {
        $model = OrderInfo::orderSelectCondition();

        $model = $model->where('user_id', $uid)
            ->where('is_zc_order', 0); //排除众筹订单

        if ($status == 1) {
            // 待付款
            $order = new \stdClass;
            $order->type = 'toBe_pay';
            $order->idTxt = 'payId';
            $order->keyword = CS_AWAIT_PAY;
            $model = $model->searchKeyword($order);
        } elseif ($status == 2) {
            // 待收货
            $order = new \stdClass;
            $order->type = 'toBe_confirmed';
            $order->idTxt = 'to_confirm_order';
            $order->keyword = CS_TO_CONFIRM;
            $model = $model->searchKeyword($order);
        } elseif ($status == 3) {
            // 已收货
            $order = new \stdClass;
            $order->type = 'toBe_finished';
            $order->idTxt = 'to_finished';
            $order->keyword = CS_FINISHED;
            $model = $model->searchKeyword($order);
        }

        if ($status == 4) {
            //回收站订单
            $model = $model->where('is_delete', 1);
        } else {
            $model = $model->where('is_delete', 0);
        }

        $order_count = $model->count();

        return $order_count;
    }

    /**
     * 获取订单所属的店铺信息
     * @param $orderId
     * @return mixed
     */
    public function getOrderStore($orderId)
    {
        $store = OrderGoods::from('order_goods as og')
            ->select('og.ru_id', 'ss.shop_title', 'ss.shop_name', 'ss.kf_qq', 'ss.kf_ww', 'ss.kf_type')
            ->join('seller_shopinfo as ss', 'ss.ru_id', 'og.ru_id')
            ->where('og.order_id', $orderId)
            ->first();

        if ($store == null) {
            return [];
        }
        return $store->toArray();
    }

    /**
     * 获取订单所属店铺名称
     * @param $orderId
     * @return mixed
     */
    public function getShopInfo($orderId)
    {
        $ru_id = OrderGoods::where('order_id', $orderId)->value('ru_id');
        if ($ru_id > 0) {
            $shop = $this->storeService->getMerchantsStoreInfo($ru_id, 1);
            $res['shop_id'] = $shop['id'];
            $res['shop_name'] = $shop['check_sellername'] == 0 ? $shop['shoprz_brandName'] : ($shop['check_sellername'] == 1 ? $shop['rz_shopName'] : $shop['shop_name']);
        } else {
            $res['shop_id'] = 0;
            $res['shop_name'] = lang('common.self_run');
        }
        return $res;
    }

    /**
     * 取消订单
     *
     * @param $uid
     * @param $order_id
     * @return array|bool
     */
    public function orderCancel($uid, $order_id)
    {
        $order = OrderInfo::where('user_id', $uid)
            ->where('order_id', $order_id)
            ->first();
        $order = $order ? $order->toArray() : [];

        if (empty($order)) {
            return [];
        }

        $data = [
            'order_status' => OS_CANCELED,
        ];
        $up = OrderInfo::where('user_id', $uid)->where('order_id', $order_id)->update($data);

        if ($up) {
            load_helper(['common', 'ecmoban', 'order']);
            /* 记录log */
            order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, lang('user.buyer_cancel'), lang('common.buyer'));
            /* 退货用户余额、积分、红包 */
            if ($order['user_id'] > 0 && $order['surplus'] > 0) {
                $change_desc = sprintf(lang('user.return_surplus_on_cancel'), $order['order_sn']);
                log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
            }
            if ($order['user_id'] > 0 && $order['integral'] > 0) {
                $change_desc = sprintf(lang('user.return_integral_on_cancel'), $order['order_sn']);
                log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
            }
            // 使用红包退回红包
            if ($order['user_id'] > 0 && $order['bonus_id'] > 0) {
                change_user_bonus($order['bonus_id'], $order['order_id'], false);
            }

            // 使用优惠券退回优惠券
            if ($order['user_id'] > 0 && $order['uc_id'] > 0) {
                unuse_coupons($order['order_id']);
            }

            /* 退回订单消费储值卡金额 */
            return_card_money($order_id);

            /* 如果使用库存，且下订单时减库存，则增加库存 */
            if ($this->config['use_storage'] == '1' && $this->config['stock_dec_time'] == SDT_PLACE) {
                change_order_goods_storage($order['order_id'], false, 1, 3);
            }

            /* 修改订单 */
            $arr = [
                'bonus_id' => 0,
                'bonus' => 0,
                'uc_id' => 0,
                'coupons' => 0,
                'integral' => 0,
                'integral_money' => 0,
                'surplus' => 0
            ];
            update_order($order['order_id'], $arr);

            /* 更新会员订单信息 */
            $dbRaw = [
                'order_nopay' => "order_nopay - 1",
            ];
            $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
            UserOrderNum::where('user_id', $uid)->where('order_nopay', '>', 0)->update($dbRaw);

            return true;
        }
        return false;
    }

    /**
     * 获得虚拟商品的卡号密码
     */
    public function get_virtual_goods_info($rec_id = 0)
    {
        $virtual_info = OrderGoods::from('order_goods as og')
            ->select('vc.*')
            ->leftjoin('order_info as oi', 'oi.order_id', 'og.order_id')
            ->leftjoin('virtual_card as vc', 'vc.order_sn', 'oi.order_sn')
            ->whereColumn('og.goods_id', 'vc.goods_id')
            ->where('vc.is_saled', 1)
            ->where('og.rec_id', $rec_id)
            ->get();
        $virtual_info = $virtual_info ? $virtual_info->toArray() : '';


        $res = [];
        $virtual = [];
        if ($virtual_info) {
            foreach ($virtual_info as $row) {
                $res['card_sn'] = $this->_decrypt($row['card_sn']);
                $res['card_password'] = $this->_decrypt($row['card_password']);
                $res['end_date'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['end_date']);
                $virtual[] = $res;
            }
        }
        return $virtual;
    }

    /**
     * 解密函数
     * @param string $str 加密后的字符串
     * @param string $key 密钥
     * @return  string  加密前的字符串
     */
    public function _decrypt($str, $key = AUTH_KEY)
    {
        $coded = '';
        $keylength = strlen($key);
        $str = base64_decode($str);

        for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength) {
            $coded .= substr($str, $i, $keylength) ^ $key;
        }

        return $coded;
    }
}
