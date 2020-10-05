<?php

namespace App\Patch;

use App\Models\AdminAction;
use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_8
{
    public function run()
    {
        $this->shopConfig();
        $this->clearCache();
        $this->offlineStore();
        $this->packageGoods();
        $this->merchantsStepsFields();
    }

    /**
     * 隐藏网站地址
     *
     * @return bool
     */
    private function shopConfig()
    {
        $count = ShopConfig::where('code', 'site_domain')->count();
        if ($count > 0) {
            ShopConfig::where('code', 'site_domain')
                ->update([
                    'type' => 'hidden',
                    'value' => ''
                ]);
        }

        ShopConfig::where('code', 'lang')
            ->update([
                'value' => 'zh-CN'
            ]);
    }

    /**
     * 补漏权限
     */
    public function addAction()
    {

        /* 促销权限 */
        $promotion_id = AdminAction::where('action_code', 'promotion')->value('action_id');

        $count = AdminAction::where('action_code', 'topic_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $promotion_id,
                'action_code' => 'topic_manage',
                'seller_show' => 1
            ]);
        }

        $count = AdminAction::where('action_code', 'snatch_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $promotion_id,
                'action_code' => 'snatch_manage',
                'seller_show' => 1
            ]);
        }

        $count = AdminAction::where('action_code', 'favourable')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $promotion_id,
                'action_code' => 'favourable',
                'seller_show' => 1
            ]);
        }

        $count = AdminAction::where('action_code', 'package_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $promotion_id,
                'action_code' => 'package_manage',
                'seller_show' => 1
            ]);
        }

        $count = AdminAction::where('action_code', 'auction')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $promotion_id,
                'action_code' => 'auction',
                'seller_show' => 1
            ]);
        }

        /* 众筹权限 */
        $zc_id = AdminAction::where('action_code', 'zc_manage')->value('action_id');
        $zc_id = $zc_id ? $zc_id : 0;

        if ($zc_id < 1) {
            $zc_id = AdminAction::insertGetId([
                'parent_id' => 0,
                'action_code' => 'zc_manage',
                'seller_show' => 0
            ]);
        }

        $count = AdminAction::where('action_code', 'zc_project_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_project_manage'
            ]);
        }

        $count = AdminAction::where('action_code', 'zc_category_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_category_manage'
            ]);
        }

        $count = AdminAction::where('action_code', 'zc_initiator_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_initiator_manage'
            ]);
        }

        $count = AdminAction::where('action_code', 'zc_topic_manage')->count();

        if ($count < 1) {
            AdminAction::insert([
                'parent_id' => $zc_id,
                'action_code' => 'zc_topic_manage'
            ]);
        }

        $action_id = AdminAction::where('action_code', 'ad_manage')->value('action_id');
        $action_id = $action_id ? $action_id : 0;

        if ($action_id < 1) {
            AdminAction::nsert([
                'parent_id' => $promotion_id,
                'action_code' => 'ad_manage',
                'seller_show' => 0
            ]);
        } else {
            AdminAction::where('action_id', $action_id)->update([
                'seller_show' => 0
            ]);
        }

        /* 删除赠品管理 */
        AdminAction::where('action_code', 'gift_manage')->delete();

        /* 网店信息管理 */
        AdminAction::where('action_code', 'shopinfo_manage')->delete();

        /* 网店帮助管理 */
        AdminAction::where('action_code', 'shophelp_manage')->delete();

        /* 在线调查管理 */
        AdminAction::where('action_code', 'vote_priv')->delete();

        /* 首页主广告管理 */
        AdminAction::where('action_code', 'flash_manage')->delete();

        /* 文件校验 */
        AdminAction::where('action_code', 'file_check')->delete();

        /* 授权证书 */
        AdminAction::where('action_code', 'shop_authorized')->delete();

        /* 网罗天下管理 */
        AdminAction::where('action_code', 'webcollect_manage')->delete();
    }

    public function offlineStore()
    {
        $table = 'offline_store';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (Schema::hasColumn($table, 'country')) {

            Schema::table($table, function (Blueprint $table) {
                $table->integer('country')->change();
            });
        }

        if (Schema::hasColumn($table, 'province')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('province')->change();
            });
        }

        if (Schema::hasColumn($table, 'city')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('city')->change();
            });
        }

        if (Schema::hasColumn($table, 'district')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('district')->change();
            });
        }

        if (Schema::hasColumn($table, 'street')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('street')->change();
            });
        }
    }

    public function packageGoods()
    {
        if (!Schema::hasTable('package_goods')) {
            return false;
        }

        Schema::table('package_goods', function (Blueprint $table) {
            $table->integer('package_id')->default(0)->comment('活动id')->change();
        });
    }

    public function merchantsStepsFields()
    {
        if (!Schema::hasColumn('merchants_steps_fields', 'is_distribution')) {
            Schema::table('merchants_steps_fields', function (Blueprint $table) {
                $table->string('is_distribution')->comment('是否开启分销');
            });
        }
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
