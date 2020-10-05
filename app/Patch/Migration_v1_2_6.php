<?php

namespace App\Patch;


use App\Models\ShopConfig;

class Migration_v1_2_6
{
    public function run()
    {
        $this->shopConfig();
        $this->clearCache();
    }

    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.2.6'
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