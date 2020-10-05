<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_3_1
{
    /**
     * @throws \Exception
     */
    public function run()
    {
        $this->warehouseAreaAttr();
        $this->orderReturn();
        $this->returnGoods();
        $this->goods();
        $this->shopConfig();
        $this->goodsInventoryLogs();
    }

    /**
     * @return bool
     */
    public function warehouseAreaAttr()
    {
        $table = 'warehouse_area_attr';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'city_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('city_id')->index('city_id')->default(0)->comment('商品仓库地区-区县ID');
            });
        }
    }

    /**
     * @return bool
     */
    public function orderReturn()
    {
        $table = 'order_return';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'return_rate_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('return_rate_price', 10, 2)->default('0.00')->comment('退税金额');
            });
        }
    }

    /**
     * @return bool
     */
    public function returnGoods()
    {
        $table = 'return_goods';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'rate_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('rate_price', 10, 2)->default('0.00')->comment('退税金额');
            });
        }
    }

    /**
     * @return bool
     */
    public function goods()
    {
        $table = 'goods';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (Schema::hasColumn($table, 'sort_order')) {

            Schema::table($table, function (Blueprint $table) {
                $table->integer('sort_order')->change();
            });
        }
    }

    /**
     * @throws \Exception
     */
    public function shopConfig()
    {
        $count = ShopConfig::where('code', 'area_pricetype')->count();

        /* 商品设置地区模式时 */
        if ($count < 1) {

            $parent_id = ShopConfig::where('code', 'goods_base')->value('id');

            ShopConfig::insert([
                'parent_id' => $parent_id,
                'code' => 'area_pricetype',
                'type' => 'select',
                'store_range' => '0,1',
                'shop_group' => 'goods'
            ]);
        } else {
            ShopConfig::where('code', 'area_pricetype')->update([
                'type' => 'select'
            ]);
        }

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.3.1'
        ]);

        $this->clearCache();
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

    /**
     * @return bool
     */
    public function goodsInventoryLogs()
    {
        $table = 'goods_inventory_logs';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'city_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('city_id')->index('city_id')->default(0)->comment('仓库城市-区县');
            });
        }
    }
}