<?php

namespace App\Patch;


use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_5
{
    public function run()
    {
        $this->changeRegionWarehouse();
        $this->shopConfig();
        $this->clearCache();
    }

    /**
     * 修改仓库地区表字段
     *
     * @return bool
     */
    private function changeRegionWarehouse()
    {
        $name = 'region_warehouse';
        if (Schema::hasTable($name)) {
            return false;
        }

        if (Schema::hasColumn($name, 'regionid')) {
            Schema::table($name, function (Blueprint $table) {
                $table->renameColumn('regionid', 'regionId');
            });
        }
    }

    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.2.5'
        ]);
    }

    /**
     * 清除缓存
     *
     * @throws \Exception
     */
    protected function clearCache()
    {
        cache()->forget('shop_config');
    }
}