<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 客服模块数据填充
 * Class KefuModuleSeeder
 */
class KefuModuleSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->adminAction();
    }

    /**
     * 菜单权限
     */
    private function adminAction()
    {
        $result = DB::table('admin_action')->where('action_code', 'services_list')->count();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'parent_id' => '4',
                    'action_code' => 'services_list',
                    'seller_show' => '1'
                ]
            ];
            DB::table('admin_action')->insert($rows);
        }
    }

}