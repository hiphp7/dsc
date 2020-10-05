<?php

namespace App\Plugins\Cron\DrpLog;

use App\Custom\Distribute\Models\DrpAccountLog;
use App\Custom\Distribute\Services\DistributeService;
use App\Models\DrpLog;
use App\Models\OrderInfo;
use App\Services\Drp\DrpConfigService;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$debug = config('app.debug'); // true 开启日志 false 关闭日志

$now = $this->timeRepository->getGmTime();
$limit = !empty($cron['auto_drp_log_count']) ? $cron['auto_drp_log_count'] : 50;//每次处理数量


$drp_config = app(DrpConfigService::class)->drpConfig();
// 是否开启自动分佣
if (isset($drp_config['settlement_type']['value']) && $drp_config['settlement_type']['value'] == 1) {

    $able_day = $drp_config['settlement_time']['value'] ?? 7;
    $aff_day = $this->timeRepository->getLocalStrtoTime(-$able_day . 'day');

    // 取drp_log日志表 未分成
    $model = DrpLog::query()->where('is_separate', 0);

    $model = $model->whereHas('getOrder', function ($query) {
        $query->where('main_count', 0)->where('pay_status', PS_PAYED)->where('shipping_status', SS_RECEIVED)->where('drp_is_separate', 0);
    })->orWhereHas('getDrpAccountLog', function ($query) {
        $query->where('is_paid', 1)->where('drp_is_separate', 0);
    });;

    $model = $model->whereHas('getUser');

    $model = $model->with([
        'getOrder' => function ($query) {
            $query->select('order_id', 'order_sn', 'money_paid', 'surplus', 'pay_time', 'shipping_status')->where('drp_is_separate', 0);
        },
        'getUser' => function ($query) {
            $query->select('user_id', 'user_name');
        },
        'getDrpAccountLog' => function ($query) {
            $query->select('id', 'user_id', 'add_time', 'drp_is_separate', 'amount', 'membership_card_id', 'paid_time');
        }
    ]);

    // 分块处理更新
    $model->chunkById($limit, function ($result) use ($now, $aff_day) {
        $result = $result ? $result->toArray() : [];

        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $value = collect($value)->merge($value['get_user'])->except('get_user')->all();

                if (empty($value['user_id']) || empty($value['user_name'])) {
                    break;
                }

                // 普通订单分成
                if (isset($value['get_order']) && !empty($value['get_order'])) {
                    $value = collect($value)->merge($value['get_order'])->except('get_order')->all();

                    // 7天可分佣时间、订单已收货
                    if ($aff_day >= $value['pay_time'] && $value['shipping_status'] == SS_RECEIVED) {

                        $change_desc = sprintf(lang('admin/drp.drp_separate_info'), $value['user_name'], $value['order_sn'], $value['money'], $value['point']);

                        app(DistributeService::class)->drp_log_account_change($value['user_id'], $value['money'], 0, 0, $value['point'], $change_desc, ACT_SEPARATE);

                        // 更新订单已分成状态
                        OrderInfo::where(['order_id' => $value['order_id']])->update(['drp_is_separate' => 1]);

                        // 更新佣金分成记录
                        DrpLog::where(['order_id' => $value['order_id']])->update(['is_separate' => 1, 'time' => $now]);
                    }

                }
                // 付费购买分成
                if (isset($value['get_drp_account_log']) && !empty($value['get_drp_account_log'])) {
                    $value = collect($value)->merge($value['get_drp_account_log'])->except('get_drp_account_log')->all();
                    // 7天可分佣时间
                    if ($aff_day >= $value['paid_time']) {
                        $change_desc = sprintf(lang('admin/drp.drp_separate_info'), $value['user_name'], $value['id'], $value['money'], $value['point']);

                        app(DistributeService::class)->drp_log_account_change($value['user_id'], $value['money'], 0, 0, $value['point'], $change_desc, ACT_SEPARATE);

                        // 更新订单已分成状态
                        DrpAccountLog::where(['id' => $value['drp_account_log_id']])->update(['drp_is_separate' => 1]);

                        // 更新佣金分成记录
                        DrpLog::where(['log_id' => $value['log_id']])->update(['is_separate' => 1, 'time' => $now]);
                    }
                }
            }
        }

    });

}



