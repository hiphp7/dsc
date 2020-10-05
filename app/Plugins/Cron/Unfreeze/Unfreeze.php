<?php

namespace App\Plugins\Cron\Unfreeze;

use App\Models\MerchantsAccountLog;
use App\Models\SellerCommissionBill;
use App\Models\SellerShopinfo;

$cron_lang = __DIR__ . '/Languages/' . config('shop.lang') . '.php';

if (file_exists($cron_lang)) {
    require_once($cron_lang);
}

$debug = config('app.debug'); // true 开启日志 false 关闭日志
$log = false;//有改变账单的时候再记录日志

$time = $this->timeRepository->getGmTime();

$limit = !empty($cron['auto_unfreeze_count']) ? $cron['auto_unfreeze_count'] : 10;//自动操作数量

// 查询 seller_commission_bill表 冻结解冻天数 > 0 并且冻结时间存在

// 冻结解冻天数：frozen_data > 0
// 冻结时间：frozen_time > 0

$model = SellerCommissionBill::where('frozen_data', '>', 0)->where('frozen_time', '>', 0);

$model = $model->limit($limit)
    ->orderBy('frozen_time', 'ASC')
    ->orderBy('frozen_data', 'ASC')
    ->get();

$bill_list = $model ? $model->toArray() : [];

if (!empty($bill_list)) {
    foreach ($bill_list as $key => $value) {

        // 账单自动解冻
        $detail = [];
        $detail['chargeoff_status'] = 2;
        if (!$value['chargeoff_time']) {
            $detail['chargeoff_time'] = $time;
        }
        $detail['frozen_money'] = 0;
        $detail['settleaccounts_time'] = $time;
        $detail['should_amount'] = $value['should_amount'] + $value['frozen_money'];

        // 更新累加商家账户余额
        updateSellerInfo($value['seller_id'], $value['frozen_money']);

        // 更新结账单
        updateSellerCommissionBill($value['id'], $detail);

        $operate = session()->has('admin_name') ? session('admin_name', '') : lang('common.auto_system');
        $change_desc = sprintf(lang('admin/merchants_commission.seller_bill_unfreeze'), $operate);
        $user_account_log = array(
            'user_id' => $value['seller_id'],
            'user_money' => $value['frozen_money'],
            'change_time' => $time,
            'change_desc' => $change_desc,
            'change_type' => 2
        );
        // 操作日志
        actionMerchantsAccountLog($user_account_log);
        $log = true;
    }
}


if ($debug == true && $log == true) {
    logResult('==================== cron unfreeze log ====================');
    logResult($bill_list);
}

/**
 * 更新累加商家账户余额
 * @param int $seller_id
 * @param int $seller_money
 * @return bool
 */
function updateSellerInfo($seller_id = 0, $seller_money = 0)
{
    if (empty($seller_id) || empty($seller_money)) {
        return false;
    }

    return SellerShopinfo::where('ru_id', $seller_id)->increment('seller_money', $seller_money);
}


/**
 * 更新结账单
 * @param int $id
 * @param array $data
 * @return bool
 */
function updateSellerCommissionBill($id = 0, $data = [])
{
    if (empty($id) || empty($data)) {
        return false;
    }

    return SellerCommissionBill::where('id', $id)->update($data);
}


/**
 * 商家账户操作日志
 * @param array $data
 * @return bool
 */
function actionMerchantsAccountLog($data = [])
{
    if (empty($data)) {
        return false;
    }

    return MerchantsAccountLog::insert($data);
}