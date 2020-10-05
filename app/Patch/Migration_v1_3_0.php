<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_3_0
{
    public function run()
    {
        $this->stages();
        $this->biaotiaoLogChange();
        $this->shopConfig();
    }

    private function stages()
    {
        $table = 'stages';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'stages_rate')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('stages_rate', 10, 2)->default('0.00')->comment('商品的费率');
            });
        }
    }

    public function biaotiaoLogChange()
    {
        $name = 'biaotiao_log_change';
        if (Schema::hasTable($name)) {
            return false;
        }

        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('log_id')->index('log_id')->default(0)->comment('白条（dsc_baitiao_log）记录ID');
            $table->decimal('original_price', 10, 2)->default('0.00')->comment('白条分期原价');
            $table->decimal('chang_price', 10, 2)->default('0.00')->comment('白条分期修改后价格');
            $table->integer('add_time')->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '白条分期金额修改记录表'");
    }

    /**
     * 更新版本
     *
     * @throws \Exception
     */
    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.3.0'
        ]);

        $count = ShopConfig::where('code', 'show_mobile')->count();

        if ($count < 1) {
            $parent_id = ShopConfig::where('code', 'extend_basic')->value('id');

            /* 是否显示手机号码 */
            ShopConfig::where('code', 'show_mobile')
                ->insert([
                    'parent_id' => $parent_id,
                    'code' => 'show_mobile',
                    'value' => 1,
                    'type' => 'hidden'
                ]);
        }

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
