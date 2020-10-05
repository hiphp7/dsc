<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->adminActionChange();
        $this->update();
        $this->addAction();
        $this->deleteAction();
        $this->upgradeTov1_4_1();
    }

    /**
     * 更新字段
     */
    public function adminActionChange()
    {
        // 判断 action_code 表字段是否存在添加
        if (Schema::hasColumn('admin_action', 'action_code')) {
            Schema::table('admin_action', function (Blueprint $table) {
                $table->string('action_code', 50)->change();
            });
        }
    }

    /**
     * 权限配置
     */
    private function update()
    {
        $count = DB::table('admin_action')->where('action_code', 'gift_manage')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'seller_show' => '0'
            ];
            DB::table('admin_action')->where('action_code', 'gift_manage')->update($rows);
        }

        $count = DB::table('admin_action')->where('action_code', 'obs_configure')->count();
        if ($count <= 0) {

            $parent_id = DB::table('admin_action')->where('action_code', 'third_party_service')->value('action_id');

            DB::table('admin_action')->insert([
                'parent_id' => $parent_id,
                'action_code' => 'obs_configure',
                'seller_show' => '0'
            ]);
        }

        $count = DB::table('admin_action')->where('action_code', 'cloud_setting')->count();
        if ($count <= 0) {

            $parent_id = DB::table('admin_action')->where('action_code', 'third_party_service')->value('action_id');

            DB::table('admin_action')->insert([
                'parent_id' => $parent_id,
                'action_code' => 'cloud_setting',
                'seller_show' => '0'
            ]);
        }

        $count = DB::table('admin_action')->where('action_code', 'drp')->count();

        if ($count > 0) {
            $action_code = [
                'drp',
                'drp_config',
                'drp_shop',
                'drp_list',
                'drp_order_list',
                'drp_set_config'
            ];
            DB::table('admin_action')->whereIn('action_code', $action_code)
                ->update([
                    'seller_show' => '0'
                ]);
        }
    }

    /**
     * 补漏权限
     */
    public function addAction()
    {

        /* 促销权限 */
        $promotion_id = DB::table('admin_action')->where('action_code', 'promotion')->value('action_id');

        $action_id = DB::table('admin_action')->where('action_code', 'topic_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $promotion_id,
                'action_code' => 'topic_manage',
                'seller_show' => 1
            ]);
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 1
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'snatch_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $promotion_id,
                'action_code' => 'snatch_manage',
                'seller_show' => 1
            ]);
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 1
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'favourable')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $promotion_id,
                'action_code' => 'favourable',
                'seller_show' => 1
            ]);
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 1
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'package_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $promotion_id,
                'action_code' => 'package_manage',
                'seller_show' => 1
            ]);
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 1
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'auction')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $promotion_id,
                'action_code' => 'auction',
                'seller_show' => 1
            ]);
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 1
            ]);
        }

        /* 众筹权限 */
        $zc_id = DB::table('admin_action')->where('action_code', 'zc_manage')->value('action_id');
        $zc_id = $zc_id ? $zc_id : 0;

        if ($zc_id < 1) {
            $zc_id = DB::table('admin_action')->insertGetId([
                'parent_id' => 0,
                'action_code' => 'zc_manage',
                'seller_show' => 0
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'zc_project_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_project_manage'
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'zc_category_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_category_manage'
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'zc_initiator_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_initiator_manage'
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'zc_topic_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_topic_manage'
            ]);
        }

        $action_id = DB::table('admin_action')->where('action_code', 'ad_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            DB::table('admin_action')->insert([
                'parent_id' => $promotion_id,
                'action_code' => 'ad_manage',
                'seller_show' => 0
            ]);
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 0
            ]);
        }

        /* 删除赠品管理 */
        DB::table('admin_action')->where('action_code', 'gift_manage')->delete();

        /* 网店信息管理 */
        DB::table('admin_action')->where('action_code', 'shopinfo_manage')->delete();

        /* 网店帮助管理 */
        DB::table('admin_action')->where('action_code', 'shophelp_manage')->delete();

        /* 在线调查管理 */
        DB::table('admin_action')->where('action_code', 'vote_priv')->delete();

        /* 首页主广告管理 */
        DB::table('admin_action')->where('action_code', 'flash_manage')->delete();

        /* 文件校验 */
        DB::table('admin_action')->where('action_code', 'file_check')->delete();

        /* 授权证书 */
        DB::table('admin_action')->where('action_code', 'shop_authorized')->delete();

        /* 网罗天下管理 */
        DB::table('admin_action')->where('action_code', 'webcollect_manage')->delete();

        /* 统计模块 */
        $action_id = DB::table('admin_action')->where('action_code', 'stats')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            $action_id = DB::table('admin_action')->insertGetId([
                'parent_id' => 0,
                'action_code' => 'stats',
                'seller_show' => 0
            ]);

            $actions = [
                [
                    'parent_id' => $action_id,
                    'action_code' => 'shop_stats' // 店铺统计
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'user_stats' // 会员统计
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'sell_analysis' // 销售分析
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'industry_analysis' // 行业分析
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'exchange' // 积分明细
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'client_report_guest' // 客户统计
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'sale_order_stats' // 订单销售统计
                ],
                [
                    'parent_id' => $action_id,
                    'action_code' => 'client_flow_stats' //  客户流量统计
                ]
            ];

            foreach ($actions as $action) {
                DB::table('admin_action')->insert($action);
            }
        } else {
            DB::table('admin_action')->where('action_id', $action_id)->update([
                'seller_show' => 0
            ]);
        }
    }

    /**
     * 删除
     */
    public function deleteAction()
    {
        $count = DB::table('admin_action')->where('action_code', 'region_store')->count();
        if ($count > 0) {
            DB::table('admin_action')->where('action_code', 'region_store')->delete();
        }

    }

    /**
     * 升级 v1.4.1
     */
    public function upgradeTov1_4_1()
    {
        //会员权益管理
        $count = DB::table('admin_action')->where('action_code', 'user_rights')->count();
        if ($count <= 0) {

            $parent_id = DB::table('admin_action')->where('action_code', 'users_manage')->value('action_id');

            DB::table('admin_action')->insert([
                'parent_id' => $parent_id,
                'action_code' => 'user_rights',
                'seller_show' => '0'
            ]);
        }
    }
}
