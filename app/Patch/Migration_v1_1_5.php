<?php

namespace App\Patch;

use App\Models\ShopConfig;

class Migration_v1_1_5
{
    public function run()
    {
        /* 优化商店logo */
        $count = ShopConfig::where('code', 'shop_logo')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'store_dir' => 'images/common/'
            ];
            ShopConfig::where('code', 'shop_logo')->update($rows);
        }
    }
}
