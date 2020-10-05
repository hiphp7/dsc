<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_9
{
    public function run()
    {
        $this->wechatUser();
        $this->shopConfig();
    }

    /**
     * 修改微信用户表
     *
     * @return bool
     */
    private function wechatUser()
    {
        $name = 'wechat_user';

        if (!Schema::hasTable($name)) {
            return false;
        }

        if (Schema::hasColumn($name, 'drp_parent_id')) {
            Schema::table($name, function (Blueprint $table) {
                //修改字段结构
                $table->integer('drp_parent_id')->default(0)->change();
            });
        }
    }

    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.2.9'
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
