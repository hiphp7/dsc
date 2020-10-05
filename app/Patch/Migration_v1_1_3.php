<?php

namespace App\Patch;

use App\Models\AdminAction;
use App\Models\Payment;
use App\Models\ShopConfig;

class Migration_v1_1_3
{
    public function run()
    {
        /* 去除复杂重写 */
        $count = ShopConfig::where('code', 'rewrite')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'store_range' => '0,1'
            ];
            ShopConfig::where('code', 'rewrite')->update($rows);
        }

        /* 去除发票类型税率 */
        $count = ShopConfig::where('code', 'invoice_type')->count();
        if ($count > 0) {
            /* 删除 */
            ShopConfig::where('code', 'invoice_type')->delete();
        }

        $count = Payment::where('pay_code', 'alipay')
            ->count();

        if ($count > 0) {
            // 默认数据
            $rows = [
                'pay_desc' => '支付宝网站(www.alipay.com) 是国内先进的网上支付平台。'
            ];
            Payment::where('pay_code', 'alipay')
                ->update($rows);
        }

        /* 刚刚权限 */
        $count = AdminAction::where('action_code', 'ad_manage')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'seller_show' => '0'
            ];
            AdminAction::where('action_code', 'ad_manage')->update($rows);
        }
    }
}
