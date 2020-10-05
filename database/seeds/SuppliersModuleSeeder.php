<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 供应链模块数据填充
 * Class SuppliersModuleSeeder
 */
class SuppliersModuleSeeder extends Seeder
{

	/**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * php artisan db:seed --class=SuppliersModuleSeeder
         */

        // 添加自定义导航菜单
        $this->add_nav();
    }


    /**
     * 添加自定义导航菜单
     */
    private function add_nav()
    {
        // 添加供应商入驻 自定义导航栏
		$result = DB::table('nav')->where('url', 'wholesale_apply.php')->count();
		if (empty($result)) {
			// 插入新数据
            $rows = [
				[
                    'name' => '供应商入驻',
                    'ifshow' => '1',
                    'url' => 'wholesale_apply.php',
                    'vieworder' => '9', // 排序
                    'opennew' => '1',
                    'type' => 'bottom'
                ]
            ];
            DB::table('nav')->insert($rows);

		}
    }

}
