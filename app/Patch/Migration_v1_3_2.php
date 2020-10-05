<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_3_2
{
    public function run()
    {
        $this->deleteTable();
        $this->shopConfig();
    }

    /**
     * 删除多余表
     */
    private function deleteTable()
    {
        $prefix = config('database.connections.mysql.prefix');

        if (Schema::hasTable('alidayu_configure')) {
            $sql = "DROP TABLE `" . $prefix . "alidayu_configure`";
            DB::statement($sql);
        }

        if (Schema::hasTable('alitongxin_configure')) {
            $sql = "DROP TABLE `" . $prefix . "alitongxin_configure`";
            DB::statement($sql);
        }
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {
        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.3.2'
        ]);

        $this->clearCache();
    }

    /**
     * 清除缓存
     *
     * @throws \Exception
     */
    private function clearCache()
    {
        cache()->forget('shop_config');
    }
}