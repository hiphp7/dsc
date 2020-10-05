<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_1_6
{
    public function run()
    {
        if (!Schema::hasColumn('order_info', 'ru_id')) {
            Schema::table('order_info', function (Blueprint $table) {
                $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            });
        }

        if (!Schema::hasColumn('order_info', 'main_count')) {
            Schema::table('order_info', function (Blueprint $table) {
                $table->smallInteger('main_count')->unsigned()->default(0)->index('main_count')->comment('子订单数量');
            });
        }

        /* 隐藏是否启用首页可视化配置 */
        $count = ShopConfig::where('code', 'openvisual')->where('type', 'select')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'type' => 'hidden'
            ];
            ShopConfig::where('code', 'openvisual')->update($rows);
        }

        // 更新版本
        $rows = [
            'value' => 'v1.1.6'
        ];
        ShopConfig::where('code', 'dsc_version')->update($rows);
    }
}
