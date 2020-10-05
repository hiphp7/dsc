<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_3_6
{
    public function run()
    {
        // 新增支付密码启用开关
        $this->usePaypwd();
        // 新增商店设置所在区域
        $this->add_shop_district();

        $this->shopConfig();

        /* 添加字段 */
        $this->orderGoods();
        $this->orderReturn();
        $this->orderInfo();

        // 添加商家入驻更新时间字段
        $this->merchantsShopInformation();

        // 优化connect_user表索引导致的慢日志查询
        $this->changeConnectUser();

        $sup_file = app_path('Modules/Suppliers/Controllers/IndexController.php');
        if (file_exists($sup_file)) {
            // 添加供应商入驻审核内容字段
            $this->addSuppliersReviewContent();
        }
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {
        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.3.6'
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

    /**
     * 新增支付密码启用开关 (购物流程配置)
     */
    private function usePaypwd()
    {
        $result = ShopConfig::where('code', 'use_paypwd')->count();
        if (empty($result)) {
            $parent_id = ShopConfig::where('code', 'shopping_flow')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 0;

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'use_paypwd',
                    'value' => 1,
                    'type' => 'select',
                    'store_range' => '1,0',
                    'sort_order' => 1,
                    'shop_group' => ''
                ]
            ];
            ShopConfig::insert($rows);
        }
    }

    /**
     * 新增商店设置所在区域
     */
    private function add_shop_district()
    {
        $result = ShopConfig::where('code', 'shop_district')->count();
        if (empty($result)) {
            $parent_id = ShopConfig::where('code', 'shop_info')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 1;

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'shop_district',
                    'value' => '',
                    'type' => 'manual',
                    'store_range' => '',
                    'sort_order' => 0,
                    'shop_group' => ''
                ]
            ];
            ShopConfig::insert($rows);

            // 修改选择地区配置排序
            $where = [
                'shop_name',
                'shop_title',
                'shop_desc',
                'shop_keywords',
                'shop_country',
                'shop_province',
                'shop_city'
            ];
            ShopConfig::whereIn('code', $where)->update(['sort_order' => 0]);
        }
    }

    /**
     * 添加订单商品表字段
     *
     * @return bool
     */
    public function orderGoods()
    {
        $table = 'order_goods';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'goods_bonus')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('goods_bonus', 10, 2)->default(0)->comment('红包均摊商品');
            });
        }
    }

    /**
     * 添加退换货订单表字段
     *
     * @return bool
     */
    public function orderReturn()
    {
        $table = 'order_return';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'goods_bonus')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('goods_bonus', 10, 2)->default(0)->comment('红包均摊商品');
            });
        }
    }

    /**
     * 添加订单表字段
     *
     * @return bool
     */
    public function orderInfo()
    {
        $table = 'order_info';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'main_pay')) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('main_pay')->default(0)->comment('主订单是否支付【0：过滤之前订单 1:未支付 2：已支付】');
            });
        }
    }

    /**
     * 添加商家入驻更新时间字段
     *
     * @return bool
     */
    protected function merchantsShopInformation()
    {
        $table = 'merchants_shop_information';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'update_time')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('update_time')->unsigned()->default(0)->comment('更新时间');
            });
        }
    }

    /**
     * 优化connect_user表索引导致的慢日志查询
     *
     * @return bool
     */
    protected function changeConnectUser()
    {
        $table = 'connect_user';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (Schema::hasColumn($table, 'open_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex('open_id');
                $table->index('open_id', 'open_id');
            });
        }
    }

    /**
     * 添加供应商入驻审核内容字段
     *
     * @return bool
     */
    protected function addSuppliersReviewContent()
    {
        $table = 'suppliers';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'review_content')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('review_content', 100)->default('')->comment('审核内容');
            });
        }
    }
}
