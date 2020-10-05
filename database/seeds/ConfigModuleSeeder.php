<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigModuleSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->shopConfig();
    }

    private function shopConfig()
    {
        /* 识别跳转H5 start */
        $other = [
            'parent_id' => 1,
            'type' => 'select',
            'store_range' => '0,1',
            'value' => 1,
            'sort_order' => 1
        ];
        $count = DB::table('shop_config')->where('code', 'auto_mobile')->count();
        if ($count > 0) {
            DB::table('shop_config')->where('code', 'auto_mobile')->update($other);
        } else {
            $other['code'] = 'auto_mobile';
            DB::table('shop_config')->insert($other);
        }
        /* 识别跳转H5 end */

        DB::table('shop_config')->where('code', 'user_account_code')->update([
            'shop_group' => 'sms'
        ]);

        /* 电子面单开关 start */
        $config_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');
        $config_id = $config_id ? $config_id : 0;

        $other = [
            'parent_id' => $config_id,
            'type' => 'select',
            'store_range' => '0,1',
            'value' => 0,
            'sort_order' => 1
        ];
        $count = DB::table('shop_config')->where('code', 'tp_api')->count();
        if ($count > 0) {
            DB::table('shop_config')->where('code', 'tp_api')->update($other);
        } else {
            $other['code'] = 'tp_api';
            DB::table('shop_config')->insert($other);
        }
        /* 电子面单开关 end */

        /* 去除复杂重写 */
        $count = DB::table('shop_config')->where('code', 'rewrite')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'store_range' => '0,1'
            ];
            DB::table('shop_config')->where('code', 'rewrite')->update($rows);
        }

        /* 去除发票类型税率 */
        $count = DB::table('shop_config')->where('code', 'invoice_type')->count();
        if ($count > 0) {
            /* 删除 */
            DB::table('shop_config')->where('code', 'invoice_type')->delete();
        }

        /* 优化商店logo */
        $count = DB::table('shop_config')->where('code', 'shop_logo')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'store_dir' => 'images/common/'
            ];
            DB::table('shop_config')->where('code', 'shop_logo')->update($rows);
        }

        /* 隐藏是否启用首页可视化配置 */
        $count = DB::table('shop_config')->where('code', 'openvisual')->where('type', 'select')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'type' => 'hidden'
            ];
            DB::table('shop_config')->where('code', 'openvisual')->update($rows);
        }

        $count = DB::table('shop_config')->where('code', 'cashier_Settlement')->count();

        if ($count <= 0) {

            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'cashier_Settlement',
                    'type' => 'hidden',
                    'value' => '1'
                ]
            ];
            DB::table('shop_config')->insert($rows);
        }
    }
}