<?php

namespace App\Patch;

use App\Models\AdminAction;
use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migration_v1_3_7
{
    public function run()
    {
        $this->sessionsFields();
        $this->sessionsDataFields();
        $this->adminAction();
        $this->addUserOrderNumTable();
        $this->addPinyinToBrand();
        $this->shopConfig();

        if (!Schema::hasColumn('category', 'touch_catads_url')) {
            Schema::table('category', function (Blueprint $table) {
                $table->string('touch_catads_url', 255)->default('')->comment('手机分类列表广告链接');
            });
        }
    }

    /**
     * 修改sessions表字段类型
     *
     * @return bool
     */
    private function sessionsFields()
    {
        $table = 'sessions';
        if (!Schema::hasTable($table)) {
            return false;
        }
        if (Schema::hasColumn($table, 'sesskey')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('sesskey', 100)->change();
            });
        }
    }

    /**
     * 修改sessions表字段类型
     *
     * @return bool
     */
    private function sessionsDataFields()
    {
        $table = 'sessions_data';
        if (!Schema::hasTable($table)) {
            return false;
        }
        if (Schema::hasColumn($table, 'sesskey')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('sesskey', 100)->change();
            });
        }
    }


    /**
     * 修改sessions表字段类型
     *
     * @return bool
     */
    private function adminAction()
    {
        $table = 'admin_action';
        if (!Schema::hasTable($table)) {
            return false;
        }

        $count = AdminAction::where('action_code', 'region_store')->count();
        if ($count > 0) {
            AdminAction::where('action_code', 'region_store')->delete();
        }

    }

    private function addUserOrderNumTable()
    {
        $table = 'user_order_num';
        if (Schema::hasTable($table)) {
            return false;
        }
        Schema::create($table, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->default(0)->unsigned()->index('user_id')->comment('会员ID');
            $table->string('user_name', 120)->default('')->comment('会员名称');
            $table->integer('order_all_num')->unsigned()->default(0)->comment('订单数量');
            $table->integer('order_nopay')->unsigned()->default(0)->comment('待付款订单数量');
            $table->integer('order_nogoods')->unsigned()->default(0)->comment('待收货订单数量');
            $table->integer('order_isfinished')->unsigned()->default(0)->comment('已完成订单数量');
            $table->integer('order_isdelete')->unsigned()->default(0)->comment('回收站订单数量');
            $table->integer('order_team_num')->unsigned()->default(0)->comment('拼团订单数量');
            $table->integer('order_not_comment')->unsigned()->default(0)->comment('待评价订单数量');
            $table->integer('order_return_count')->unsigned()->default(0)->comment('待同意状态退换货申请数量');
        });
    }

    private function addPinyinToBrand()
    {
        $table = 'brand';
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'name_pinyin')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('name_pinyin', 255)->default('')->comment('品牌拼音名称');
            });
        }
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {
        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.3.7'
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
}
