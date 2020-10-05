<?php

namespace App\Plugins\Cron\Delivery;

use App\Models\OrderAction;
use App\Models\OrderInfo;
use App\Repositories\Common\TimeRepository;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$debug = config('app.debug'); // true 开启日志 false 关闭日志

$time = app(TimeRepository::class)->getGmTime();

// 配置 - 每次执行操作数量
$limit = isset($cron['auto_delivery_order_count']) && !empty($cron['auto_delivery_order_count']) ? $cron['auto_delivery_order_count'] : 10;

// 是否开启自动确认收货 0 关闭,1 开启
$open_delivery_time = $this->config['open_delivery_time'] ?? 0;

if ($open_delivery_time == 1) {
    // 查询 已付款、已发货的订单

    // 订单状态：已确认 OS_CONFIRMED 1 、已分单 OS_SPLITED 5、退货 OS_RETURNED 4
    // 支付状态：已付款 PS_PAYED 2
    // 配送状态：已发货 SS_SHIPPED 1
    $model = OrderInfo::where('main_count', 0)->whereIn('order_status', [OS_CONFIRMED, OS_RETURNED, OS_SPLITED])->where('pay_status', PS_PAYED)->where('shipping_status', SS_SHIPPED);

    $order_list = $model->limit($limit)
        ->orderBy('order_id', 'ASC')
        ->get();

    $order_list = $order_list ? $order_list->toArray() : [];

    if (!empty($order_list)) {
        foreach ($order_list as $key => $value) {
            $delivery_time = !empty($value['shipping_time']) ? $value['shipping_time'] + 24 * 3600 * $value['auto_delivery_time'] : ''; // 订单应收货时间
            if (!empty($delivery_time) && $time >= $delivery_time) {
                // 自动确认发货操作
                $data = [
                    'order_status' => $value['order_status'],
                    'shipping_status' => SS_RECEIVED,
                    'pay_status' => PS_PAYED,
                ];
                OrderInfo::where('order_id', $value['order_id'])->update($data);

                // 操作日志
                order_action_cron($value['order_sn'], $value['order_status'], SS_RECEIVED, PS_PAYED, lang('common.self_motion_goods'), lang('common.auto_system'), 0, $time);
            }
        }
    }

    if ($debug == true && $order_list) {
        logResult('==================== cron delivery log ====================', [], 'info', 'single');
        logResult($order_list, [], 'info', 'single');
    }
}


/**
 * 记录订单操作记录
 *
 * @param int $order_id 订单编号
 * @param int $order_status 订单状态
 * @param int $shipping_status 配送状态
 * @param int $pay_status 付款状态
 * @param string $note 备注
 * @param string $username 用户名，用户自己的操作则为 buyer
 * @param int $place
 * @param int $confirm_take_time 确认收货时间
 * @return  void
 */
function order_action_cron($order_id = 0, $order_status = 0, $shipping_status = 0, $pay_status = 0, $note = '', $username = '', $place = 0, $confirm_take_time = 0)
{
    if ($order_id > 0) {
        $place = !is_null($place) ? $place : '';
        $note = !is_null($note) ? $note : '';

        $other = [
            'order_id' => $order_id,
            'action_user' => $username,
            'order_status' => $order_status,
            'shipping_status' => $shipping_status,
            'pay_status' => $pay_status,
            'action_place' => $place,
            'action_note' => $note,
            'log_time' => $confirm_take_time
        ];
        OrderAction::insert($other);
    }
}
