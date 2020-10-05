<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * APP模块数据填充
 * Class AppModuleSeeder
 */
class AppModuleSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 广告默认数据
        $this->appAdPosition();
        $this->adminAction();

        // 处理 ecjia app登录旧用户数据
        $this->handleConnectUser();
    }

    /**
     * 广告位
     */
    private function appAdPosition()
    {
        $result = DB::table('app_ad_position')->where('position_type', 'loading_screen')->count();
        if (empty($result)) {
            // 插入新数据
            $row = [
                'position_name' => 'app启动加载页',
                'ad_width' => '750',
                'ad_height' => '1920',
                'position_desc' => 'app启动加载页banner',
                'position_type' => 'loading_screen',
            ];
            $position_id = DB::table('app_ad_position')->insertGetId($row);

            // 插入广告
            $this->appAd($position_id);
        }
    }

    /**
     * 广告
     */
    private function appAd($position_id = 0)
    {
        if ($position_id > 0) {
            $result = DB::table('app_ad')->where('position_id', $position_id)->count();
            if (empty($result)) {
                // 插入新数据
                $rows = [
                    [
                        'position_id' => $position_id,
                        'media_type' => '0',
                        'ad_name' => 'app启动页banner-01',
                        'ad_code' => 'data/attached/app/20190329165752.jpg',
                        'sort_order' => '50',
                        'enabled' => '1',
                    ],
                    [
                        'position_id' => $position_id,
                        'media_type' => '0',
                        'ad_name' => 'app启动页banner-02',
                        'ad_code' => 'data/attached/app/20190329165734.jpg',
                        'sort_order' => '50',
                        'enabled' => '1',
                    ]
                ];
                DB::table('app_ad')->insert($rows);
            }
        }

    }

    /**
     * 权限
     */
    private function adminAction()
    {
        $result = DB::table('admin_action')->where('action_code', 'app')->count();
        if (empty($result)) {
            // 默认数据
            $row = [
                'parent_id' => 0,
                'action_code' => 'app',
                'seller_show' => 0
            ];
            $action_id = DB::table('admin_action')->insertGetId($row);

            // 默认数据
            $rows = [
                [
                    'parent_id' => $action_id,
                    'action_code' => 'app_config',
                    'seller_show' => 0
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'app_ad_position',
                    'seller_show' => 0
                ]
            ];
            DB::table('admin_action')->insert($rows);
        }

    }

    /**
     * 处理 ecjia app登录旧用户数据
     */
    private function handleConnectUser()
    {
        DB::table('connect_user')->where('connect_code', '')->update(['connect_code' => 'app']);
    }
}