<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_3_8
{
    public function run()
    {
        $this->category();
        $this->affirm_received_sms_switch();
        $this->shopConfig();
    }

    private function category()
    {
        $table = 'category';

        if (Schema::hasColumn($table, 'rate')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('rate', 10, 2)->default('0.00')->comment('海关税率')->change();
            });
        }
    }

    private function affirm_received_sms_switch()
    {
        /* 确认收货发短信开关 */
        $count = ShopConfig::where('code', 'sms_order_received')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '8',
                'code' => 'sms_order_received',
                'type' => 'select',
                'store_range' => '1,0',
                'value' => '0',
                'sort_order' => '13',
                'shop_group' => 'sms'
            ];
            ShopConfig::insert($rows);
        }

        /* 商家确认收货发短信开关 */
        $count = ShopConfig::where('code', 'sms_shop_order_received')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '8',
                'code' => 'sms_shop_order_received',
                'type' => 'select',
                'store_range' => '1,0',
                'value' => '0',
                'sort_order' => '13',
                'shop_group' => 'sms'
            ];
            ShopConfig::insert($rows);
        }
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {
        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.3.8'
        ]);

        $this->clearCache();
    }

    /**
     * @throws \Exception
     */
    private function clearCache()
    {
        cache()->forget('shop_config');
    }
}
