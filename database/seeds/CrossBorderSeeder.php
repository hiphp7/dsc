<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrossBorderSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 是否开启实名认证验证
        $this->identityAuthStatus();

        // 初始商品分类发票税率
//        $cat_id = 1;
//        $rate = '0.16';
//        $this->updateCategoryRate($cat_id, $rate);
    }

    /**
     * 初始商品分类发票税率
     * @param int $cat_id
     * @param string $rate
     */
    protected function updateCategoryRate($cat_id = 0, $rate = '')
    {
        if ($cat_id > 0) {
            // 当前分类、子级分类
            DB::table('category')->where('cat_id', $cat_id)->orWhere('parent_id', $cat_id)->update(['rate' => $rate]);
        } else {
            DB::table('category')->update(['rate' => $rate]);
        }
    }

    /**
     * 是否开启实名认证验证
     */
    protected function identityAuthStatus()
    {
        $result = DB::table('shop_config')->where('code', 'identity_auth_status')->count();
        if (empty($result)) {
            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 0;

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'identity_auth_status',
                    'value' => 0,
                    'type' => 'hidden',
                    'shop_group' => 'identity'
                ]
            ];
            DB::table('shop_config')->insert($rows);
        }
    }
}
