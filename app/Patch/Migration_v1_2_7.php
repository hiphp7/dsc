<?php

namespace App\Patch;


use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_7
{
    public function run()
    {
        $this->merchantsStepsFields();
        $this->category();
        $this->orderGoods();
        $this->orderInfo();
        $this->merchantsCategory();
        $this->goods();
        $this->shopConfig();
        $this->clearCache();
    }

    private function merchantsStepsFields()
    {
        $table = 'merchants_steps_fields';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'source')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('source', 10)->comment('跨境-仓库来源');
            });
        }

        if (!Schema::hasColumn($table, 'country')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('country')->comment('国家/地区');
            });
        }
    }

    private function category()
    {

        $table = 'category';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rate')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('rate', 10, 2)->default(0)->comment('海关税率');
            });
        }
    }

    private function orderGoods()
    {

        $table = 'order_goods';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rate_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('rate_price', 10, 2)->comment('税费金额');
            });
        }
    }

    private function orderInfo()
    {
        $table = 'order_info';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rel_name')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('rel_name')->comment('真实姓名');
                $table->string('id_num')->comment('身份证号码');
                $table->decimal('rate_fee', 10, 2)->default('0.00')->comment('综合税费');
            });
        }
    }

    private function merchantsCategory()
    {
        $table = 'merchants_category';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rate')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('rate')->default(0)->comment('废弃字段');
            });
        }
    }

    private function goods()
    {
        $table = 'goods';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'free_rate')) {
            Schema::table($table, function (Blueprint $table) {
                $table->tinyInteger('free_rate')->default(1)->comment('是否免税: 0 免税 1 关税');
            });
        }
    }


    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.2.7'
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