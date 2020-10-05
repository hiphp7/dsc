<?php

namespace App\Patch;

use App\Models\AdminAction;
use App\Models\Category;
use App\Models\PresaleCat;
use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_4_3
{
    public function run()
    {
        $this->delSqlData();

        if (file_exists(MOBILE_DRP)) {
            $this->addDrpLogFields();
        }

        $this->userRankRights();
        $this->userMembershipRights();

        $this->PcLoginRight();
        $this->cart();
        $this->orderGoods();
        $this->printSpecification();//打印规格设置 A4规格统一
        $this->adminAction();
        $this->users();
        $this->shopConfig();
    }

    //打印规格设置 A4规格统一
    private function printSpecification()
    {
        DB::table('order_print_size')->where('specification', 'A4纸张')->update([
            'specification' => 'A4'
        ]);

        DB::table('order_print_setting')->where('specification', 'A4纸张')->update([
            'specification' => 'A4'
        ]);

        $name = 'wholesale_order_print_setting';
        if (Schema::hasTable($name)) {
            DB::table($name)->where('specification', 'A4纸张')->update([
                'specification' => 'A4'
            ]);
        }
    }

    private function adminAction()
    {
        AdminAction::where('action_code', 'wxapp_config')->delete();

        $count = AdminAction::where('action_code', 'drp')->count();

        if ($count > 0) {
            $action_code = [
                'drp',
                'drp_config',
                'drp_shop',
                'drp_list',
                'drp_order_list',
                'drp_set_config'
            ];
            AdminAction::whereIn('action_code', $action_code)
                ->update([
                    'seller_show' => '0'
                ]);
        }
    }

    private function users()
    {
        $tableName = 'users';
        if (!Schema::hasColumn($tableName, 'drp_bind_time')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('drp_bind_time')->unsigned()->default(0)->comment('分销父级绑定时间');
                $table->integer('drp_bind_update_time')->unsigned()->default(0)->comment('绑定更新时间');
            });
        }
    }

    public function cart()
    {
        $table = 'cart';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'cost_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('cost_price', 10, 2)->default(0)->comment('商品成本价');
            });
        }
    }

    public function orderGoods()
    {

        $table = 'order_goods';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'cost_price')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('cost_price', 10, 2)->default(0)->comment('商品成本价');
            });
        }
    }

    public function addDrpLogFields()
    {
        $table = 'drp_log';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'level_percent')) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('level_percent', 10, 2)->default(0)->comment('层级佣金比例');
            });
        }

        if (!Schema::hasColumn($table, 'drp_account_log_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('drp_account_log_id')->unsigned()->default(0)->comment('支付订单id,关联drp_account_log表');
            });
        }
    }

    public function userRankRights()
    {
        $name = 'user_rank_rights';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('rights_id')->unsigned()->default(0)->index()->comment('权益id,关联 user_membership');
            $table->text('rights_configure')->comment('权益配置,序列化');
            $table->integer('user_rank_id')->unsigned()->default(0)->index()->comment('会员等级id,关联 user_rank.rank_id');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员等级权益表'");
    }

    public function userMembershipRights()
    {
        $name = 'user_membership_rights';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 60)->comment('权益名称');
            $table->string('code', 60)->comment('权益code');
            $table->string('description')->default('')->comment('权益说明');
            $table->string('icon', 120)->default('')->comment('权益图标');
            $table->text('rights_configure')->comment('权益配置,序列化');
            $table->string('trigger_point', 30)->default('direct')->comment('触发方式: direct(直接),manual(手动),scheduled(定时)');
            $table->string('trigger_configure')->default('')->comment('触发配置');
            $table->integer('enable')->unsigned()->default(0)->comment('权益状态：0 关闭 1 开启');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
            $table->integer('sort')->unsigned()->default(50)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员权益表'");
    }

    /**
     * 删除默认数据的无效数据
     */
    private function delSqlData()
    {
        PresaleCat::where('cat_name', '智能尖货')->delete();
        Category::where('cat_name', '乐视电视')->delete();
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.4.3'
        ]);

        /* 去除一步购物 */
        $count = ShopConfig::where('code', 'one_step_buy')->count();
        if ($count > 0) {
            /* 删除 */
            ShopConfig::where('code', 'one_step_buy')->delete();
        }

        /* 253创蓝短信 */
        /* 253创蓝短信 用户名*/
        $count = ShopConfig::where('code', 'chuanglan_account')->count();
        if ($count == 0) {
            $parent_id = ShopConfig::where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_account',
                'type' => 'text',
            ];
            ShopConfig::insert($rows);
        }
        /* 253创蓝短信 密码*/
        $count = ShopConfig::where('code', 'chuanglan_password')->count();
        if ($count == 0) {
            $parent_id = ShopConfig::where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_password',
                'type' => 'text',
            ];
            ShopConfig::insert($rows);
        }
        /* 253创蓝短信 请求地址*/
        $count = ShopConfig::where('code', 'chuanglan_api_url')->count();
        if ($count == 0) {
            $parent_id = ShopConfig::where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_api_url',
                'type' => 'text',
            ];
            ShopConfig::insert($rows);
        }
        /* 253创蓝短信 签名*/
        $count = ShopConfig::where('code', 'chuanglan_signa')->count();
        if ($count == 0) {
            $parent_id = ShopConfig::where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_signa',
                'type' => 'text',
            ];
            ShopConfig::insert($rows);
        }
        /* 253创蓝短信 end*/

        $this->clearCache();
    }

    /**
     * 我要开店
     */
    private function PcLoginRight()
    {
        $count = DB::table('shop_config')->where('code', 'login_right')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '942',
                'code' => 'login_right',
                'type' => 'text',
                'value' => '我要开店',
                'sort_order' => '1'
            ];
            DB::table('shop_config')->insert($rows);
        }

        $count = DB::table('shop_config')->where('code', 'login_right_link')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '942',
                'code' => 'login_right_link',
                'type' => 'text',
                'value' => 'merchants.php',
                'sort_order' => '1'
            ];
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * @throws \Exception
     */
    private function clearCache()
    {
        cache()->forget('shop_config');
    }
}
