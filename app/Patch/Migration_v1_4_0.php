<?php

namespace App\Patch;

use App\Models\ShopConfig;

class Migration_v1_4_0
{
    public function run()
    {
        $this->shopConfig();
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {
        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.4.0'
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