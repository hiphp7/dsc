<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_4_1
{
    public function run()
    {
        $this->shopConfig();
        $this->adminUser();
        $this->adminAction();
        $this->storeUser();
        $this->sellerCommissionBill();
        $this->sellerNegativeBill();
        $this->sellerNegativeOrder();

        if (file_exists(MOBILE_DRP)) {
            $this->create_user_membership_rights();
            $this->create_user_membership_card();
            $this->create_user_membership_card_rights();
            $this->change_drp_shop_add_card_id();
            $this->change_drp_v141_add_fileds();

            $this->DRPModuleSeeder1_4_1();
        }
    }

    /*
    * 后台登录状态
    */
    private function adminUser()
    {
        $table = 'admin_user';

        if (!Schema::hasColumn($table, 'login_status')) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('login_status')->comment('登录状态');
            });
        }
    }

    /*
    * 后台权限 - 会员权益
    */
    private function adminAction()
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

    /*
    * 门店登录状态
    */
    private function storeUser()
    {
        $table = 'store_user';

        if (!Schema::hasColumn($table, 'login_status')) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('login_status')->comment('登录状态');
            });
        }

    }

    private function create_user_membership_rights()
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

    private function create_user_membership_card()
    {
        $name = 'user_membership_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 60)->default('')->comment('权益卡名称');
            $table->tinyInteger('type')->unsigned()->default(1)->comment('权益卡类型： 1 普通权益卡 2 分销权益卡');
            $table->string('description')->default('')->comment('权益卡说明');
            $table->string('background_img')->default('')->comment('权益卡背景图');
            $table->string('background_color')->default('')->comment('权益卡背景颜色');
            $table->text('receive_value')->comment('权益卡领取条件配置,序列化');
            $table->string('expiry_type')->default('forever')->comment('过期时间类型： forever(永久), days(多少天数), timespan(时间间隔)');
            $table->string('expiry_date')->default('')->comment('过期时间');
            $table->integer('enable')->unsigned()->default(0)->comment('权益卡状态：0 关闭 1 开启');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
            $table->integer('sort')->unsigned()->default(50)->comment('排序');
            $table->integer('user_rank_id')->unsigned()->default(0)->index()->comment('用户等级ID，关联user_rank.rank_id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '权益卡表'");
    }

    private function create_user_membership_card_rights()
    {
        $name = 'user_membership_card_rights';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('rights_id')->unsigned()->default(0)->index()->comment('权益id,关联 user_membership');
            $table->text('rights_configure')->comment('权益配置,序列化');
            $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('权益卡id,关联 user_membership_card');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '权益卡权益绑定关系表'");
    }

    private function change_drp_shop_add_card_id()
    {
        $tableName = 'drp_shop';
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益id,关联 user_membership_card');
            });
        }
        if (!Schema::hasColumn($tableName, 'open_time')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('open_time')->unsigned()->default(0)->comment('开店时间，权益开始时间');
            });
        }
        if (!Schema::hasColumn($tableName, 'expiry_time')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('expiry_time')->unsigned()->default(0)->comment('权益过期时间');
            });
        }
    }

    private function change_drp_v141_add_fileds()
    {
        $tableName = 'goods'; // 商品表
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益卡id,关联 user_membership_card');
            });
        }

        $tableName = 'order_goods'; // 订单商品表
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益卡id,关联 user_membership_card');
            });
        }

        $tableName = 'drp_shop'; // 分销商表
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_status')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_status')->unsigned()->default(0)->comment('会员权益卡状态： 0 关闭、1 开启');
            });
        }

        $tableName = 'drp_log';
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益卡id,关联 user_membership_card');
            });
        }
        if (!Schema::hasColumn($tableName, 'log_type')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('log_type')->unsigned()->default(0)->index()->comment('日志类型：0 订单分成、1 付费购买分成、 2 购买指定商品分成');
            });
        }

        $tableName = 'pay_log';
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益卡id,关联 user_membership_card');
            });
        }

        $tableName = 'drp_account_log'; // 分销商记录表
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益卡id,关联 user_membership_card');
            });
        }
        if (!Schema::hasColumn($tableName, 'receive_type')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('receive_type')->default('')->comment('会员权益卡领取类型');
            });
        }
    }

    private function sellerCommissionBill()
    {
        $name = 'seller_commission_bill';
        if (!Schema::hasTable($name)) {
            return false;
        }

        if (!Schema::hasColumn($name, 'negative_amount')) {
            Schema::table($name, function (Blueprint $table) {
                $table->decimal('negative_amount', 10, 2)->unsigned()->default('0.00')->comment('负账单金额');
            });
        }
    }

    private function sellerNegativeBill()
    {
        $name = 'seller_negative_bill';
        if (Schema::hasTable($name)) {
            return false;
        }

        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('bill_sn', 30)->default('')->comment('负账单单号');
            $table->string('commission_bill_sn', 30)->default('账单编号');
            $table->integer('commission_bill_id')->unsigned()->default(0)->index('commission_bill_id')->comment('账单ID');
            $table->integer('seller_id')->unsigned()->default(0)->index('seller_id')->comment('商家ID');
            $table->decimal('return_amount', 10, 2)->unsigned()->default('0.00')->comment('负账单总金额');
            $table->decimal('return_shippingfee', 10, 2)->unsigned()->default('0.00')->comment('负账单退款总金额');
            $table->boolean('chargeoff_status')->unsigned()->default(0)->comment('账单状态（0 未处理， 1已处理）');
            $table->integer('start_time')->unsigned()->default(0)->comment('负账单开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('负账单结束时间');
        });
    }

    public function sellerNegativeOrder()
    {
        $name = 'seller_negative_order';
        if (Schema::hasTable($name)) {
            return false;
        }

        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('negative_id')->unsigned()->default(0)->index('negative_id')->comment('负账单ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->string('order_sn', 30)->default('')->comment('订单单号');
            $table->integer('ret_id')->unsigned()->default(0)->index('ret_id')->comment('单品退货订单ID');
            $table->string('return_sn', 30)->default('')->comment('退货订单单号');
            $table->integer('seller_id')->unsigned()->default(0)->index('seller_id')->comment('商家ID');
            $table->decimal('return_amount', 10, 2)->unsigned()->default('0.00')->comment('退款金额');
            $table->decimal('return_shippingfee', 10, 2)->unsigned()->default('0.00')->comment('退运费金额');
            $table->boolean('settle_accounts')->unsigned()->default(0)->comment('账单订单结算状态（0 未结算， 1已结算）');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });
    }

    /**
     * 升级 v1.4.1
     */
    public function DRPModuleSeeder1_4_1()
    {
        // 更新排序
        DB::table('drp_config')->update(['sort_order' => '50']);

        // 还原1.4版本隐藏的 分销商审核字段
        DB::table('drp_config')->whereIn('code', ['ischeck'])->update(['type' => 'radio']);
        // 隐藏原 分销比例设置
        DB::table('drp_config')->whereIn('code', ['drp_affiliate'])->update(['type' => 'hidden']);


        // 分销配置重新分组： 空 基本配置、 show 显示配置、 scale 结算配置、qrcode 分享配置、message 消息配置

        DB::table('drp_config')->whereIn('code', ['register', 'ischeck'])->update(['group' => '']);

        DB::table('drp_config')->whereIn('code', ['notice', 'novice', 'isdrp', 'custom_distributor', 'custom_distribution', 'commission', 'agreement_id', 'count_commission', 'articlecatid'])->update(['group' => 'show']);

        DB::table('drp_config')->whereIn('code', ['withdraw', 'draw_money'])->update(['group' => 'scale']);

        DB::table('drp_config')->whereIn('code', ['issend'])->update(['group' => 'message']);


        // 新增配置
        $drp_config = DB::table('drp_config')->where('code', 'drp_affiliate')->value('value');
        if ($drp_config) {
            $drp_config = unserialize($drp_config);
        }
        // 开启VIP分销
        $drp_affiliate_on = DB::table('drp_config')->where('code', 'drp_affiliate_on')->count();
        if (empty($drp_affiliate_on)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'drp_affiliate_on',
                    'type' => 'radio',
                    'store_range' => '0,1',
                    'value' => $drp_config['on'] ?? 1,
                    'name' => '开启VIP分销',
                    'warning' => '如果关闭则不会计算分销佣金',
                    'sort_order' => '0',
                    'group' => '',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        // 佣金结算时间
        $settlement_time = DB::table('drp_config')->where('code', 'settlement_time')->count();
        if (empty($settlement_time)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'settlement_time',
                    'type' => 'text',
                    'store_range' => '',
                    'value' => $drp_config['config']['day'] ?? 7,
                    'name' => '佣金分成时间',
                    'warning' => '设置会员确认收货后，X天后，生成分成订单。单位：天，默认7天',
                    'sort_order' => '50',
                    'group' => 'scale',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        // 佣金结算时机
        $settlement_type = DB::table('drp_config')->where('code', 'settlement_type')->count();
        if (empty($settlement_type)) {
            // 插入新数据
            $rows = [
                [
                    'code' => 'settlement_type',
                    'type' => 'radio',
                    'store_range' => '0,1', // 0 禁用即手动 、1 启用即自动
                    'value' => '0',
                    'name' => '是否自动分佣',
                    'warning' => '设置禁用即手动，则订单确认收货后，过了结算分成时间，生成的分成订单需手动点击分成;<br/>设置启用即自动，则订单确认收货后，过了结算分成时间，系统将会自动分成',
                    'sort_order' => '51',
                    'group' => 'scale',
                ]
            ];
            DB::table('drp_config')->insert($rows);
        }

        //会员分销权益卡管理
        $count = DB::table('admin_action')->where('action_code', 'drpcard_manage')->count();
        if ($count <= 0) {

            $parent_id = DB::table('admin_action')->where('action_code', 'drp')->value('action_id');

            DB::table('admin_action')->insert([
                'parent_id' => $parent_id,
                'action_code' => 'drpcard_manage',
                'seller_show' => '0'
            ]);
        }

        DB::table('drp_config')->where('code', ['drp_affiliate_on'])->update(['sort_order' => '0']);
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {
        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.4.1'
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
