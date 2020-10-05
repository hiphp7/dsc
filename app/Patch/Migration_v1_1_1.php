<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_1_1
{
    public function run()
    {
        /* 分类表 */
        $name = 'category';
        if (!Schema::hasColumn($name, 'touch_catads')) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('touch_catads', 255)->default('')->comment('手机分类模块广告图');
            });
        }

        /* 识别跳转H5 start */
        $other = [
            'parent_id' => 1,
            'type' => 'select',
            'store_range' => '0,1',
            'value' => 1,
            'sort_order' => 1
        ];
        $count = ShopConfig::where('code', 'auto_mobile')->count();
        if ($count > 0) {
            ShopConfig::where('code', 'auto_mobile')->update($other);
        } else {
            $other['code'] = 'auto_mobile';
            ShopConfig::insert($other);
        }
        /* 识别跳转H5 end */

        ShopConfig::where('code', 'user_account_code')->update([
            'shop_group' => 'sms'
        ]);

        /* 电子面单开关 start */
        $config_id = ShopConfig::where('code', 'extend_basic')->value('id');
        $config_id = $config_id ? $config_id : 0;

        $other = [
            'parent_id' => $config_id,
            'type' => 'select',
            'store_range' => '0,1',
            'value' => 0,
            'sort_order' => 1
        ];
        $count = ShopConfig::where('code', 'tp_api')->count();
        if ($count > 0) {
            ShopConfig::where('code', 'tp_api')->update($other);
        } else {
            $other['code'] = 'tp_api';
            ShopConfig::insert($other);
        }
        /* 电子面单开关 end */
    }
}
