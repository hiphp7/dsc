<?php

namespace App\Patch;

use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_4_4
{
    public function run()
    {

        $this->changeMemberPriceAddPercentage();
        $this->changeGoodsAddIsDiscount();

        // v1.4.4 隐藏原短信配置
        $this->hiddenSms();
        $this->createSms();

        // 处理 ecjia app登录旧用户数据
        $this->handleConnectUser();

        if (file_exists(MOBILE_DRP)) {
            $this->upgradeTov1_4_4();

            $this->change_drp_shop_add_apply_time();

            $this->create_order_info_membership_card();
            $this->change_order_goods_add_membership_card_discount_price();
            $this->change_return_goods_add_membership_card_discount_price();
        }

        $this->orderInfo();
        $this->orderGoods();
        $this->orderReturn();

        $this->shopConfig();
    }

    private function change_drp_shop_add_apply_time()
    {
        $tableName = 'drp_shop';

        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'apply_time')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('apply_time')->unsigned()->default(0)->comment('权益申请时间');
            });
        }

        if (!Schema::hasColumn($tableName, 'expiry_type')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('expiry_type')->default('')->comment('权益卡有效期类型：forever(永久), days(多少天数), timespan(时间间隔)');
            });
        }
    }


    private function change_return_goods_add_membership_card_discount_price()
    {
        $tableName = 'return_goods'; // 退换货订单商品表

        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_discount_price')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('membership_card_discount_price', 10, 2)->unsigned()->default(0.00)->comment('退款订单商品购买权益卡折扣');
            });
        }
    }

    private function change_order_goods_add_membership_card_discount_price()
    {
        $tableName = 'order_goods';
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'membership_card_discount_price')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('membership_card_discount_price', 10, 2)->unsigned()->default(0.00)->comment('订单商品购买权益卡折扣');
            });
        }
    }

    private function create_order_info_membership_card()
    {
        $name = 'order_info_membership_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->comment('关联订单表order_id');
            $table->integer('user_id')->unsigned()->default(0)->comment('关联会员表user_id');
            $table->decimal('order_amount', 10, 2)->unsigned()->default(0.00)->comment('购买权益卡订单应付金额(不含购买权益卡金额)');
            $table->integer('membership_card_id')->unsigned()->default(0)->comment('购买会员权益卡ID');
            $table->decimal('membership_card_buy_money', 10, 2)->unsigned()->default(0.00)->comment('购买会员权益卡金额');
            $table->decimal('membership_card_discount_price', 10, 2)->unsigned()->default(0.00)->comment('购买会员权益卡折扣');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '订单开通购买会员权益卡表'");
    }

    /**
     * 新增短信插件表
     * @return bool
     */
    private function createSms()
    {
        $name = 'sms';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 60)->comment('短信名称');
            $table->string('code', 60)->comment('短信code');
            $table->string('description')->default('')->comment('短信说明');
            $table->text('sms_configure')->comment('短信配置,序列化');
            $table->tinyInteger('enable')->unsigned()->default(0)->comment('启用状态：0 关闭 1 开启');
            $table->tinyInteger('default')->unsigned()->default(0)->comment('是否默认，0 否 1 是');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
            $table->integer('sort')->unsigned()->default(50)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '短信插件表'");
    }

    /**
     * 修改商品表
     */
    private function changeGoodsAddIsDiscount()
    {
        $tableName = 'goods';
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'is_discount')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('is_discount')->default(0)->comment('是否参与会员特价权益: 0 否，1 是');
            });
        }
    }

    /**
     * 修改会员等级价格表
     */
    private function changeMemberPriceAddPercentage()
    {
        $tableName = 'member_price';
        if (!Schema::hasColumn($tableName, 'percentage')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->tinyInteger('percentage')->unsigned()->default(0)->comment('是否使用百分比：0否，1是; 配合member_price表 user_price');
            });
        }
    }

    private function orderReturn()
    {
        $tableName = 'order_return';
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        if (!Schema::hasColumn($tableName, 'goods_coupons')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('goods_coupons', 10, 2)->default(0)->comment('优惠券均摊商品');
            });
        }
    }

    private function orderInfo()
    {
        $tableName = 'order_info';
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        if (!Schema::hasColumn($tableName, 'child_show')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->tinyInteger('child_show')->default(0)->comment('当有主订单并未付款的状态情况下，操作其中的一个子订单为[分单、付款、发货]时，会员中心列表则显示');
            });
        }
    }

    private function orderGoods()
    {
        $tableName = 'order_goods';
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        if (!Schema::hasColumn($tableName, 'goods_coupons')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('goods_coupons', 10, 2)->default(0)->comment('优惠券均摊商品');
            });
        }

        if (Schema::hasColumn($tableName, 'goods_bonus')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('goods_bonus', 10, 2)->change();
            });
        }
    }

    /**
     * v1.4.4 隐藏旧短信配置
     */
    private function hiddenSms()
    {
        $code_arr = [
            // 互亿
            'sms_ecmoban_user',
            'sms_ecmoban_password',
            // 阿里大于
            'ali_appkey',
            'ali_secretkey',
            // 阿里云
            'access_key_id',
            'access_key_secret',
            // 模板堂
            'dsc_appkey',
            'dsc_appsecret',
            // 华为云
            'huawei_sms_key',
            'huawei_sms_secret',
            // 创蓝
            'chuanglan_account',
            'chuanglan_password',
            'chuanglan_api_url',
            'chuanglan_signa',
        ];
        DB::table('shop_config')->where('type', '<>', 'hidden')->where(function ($query) use ($code_arr) {
            $query->whereIn('code', $code_arr)->orWhere('code', 'sms_type');
        })->update(['type' => 'hidden']);
    }

    /**
     * 处理 ecjia app登录旧用户数据
     */
    private function handleConnectUser()
    {
        DB::table('connect_user')->where('connect_code', '')->update(['connect_code' => 'app']);
    }

    /**
     * 升级 v1.4.4
     */
    protected function upgradeTov1_4_4()
    {
        // 还原分销内购模式
        DB::table('drp_config')->where('code', ['isdistribution'])->update(['type' => 'radio', 'store_range' => '0,1,2', 'warning' => '']);
        DB::table('drp_config')->where('code', ['drp_affiliate_mode'])->update(['warning' => '', 'name' => '分销商业绩归属']);

        // 更新旧分销商申请时间
        DB::table('drp_shop')->where('apply_time', '')->where('create_time', '<>', '')
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    DB::table('drp_shop')
                        ->where('id', $user->id)
                        ->update(['apply_time' => $user->create_time]);
                }
            });

        // 更新旧分销商权益卡领取有效期类型
        DB::table('drp_shop')->where('expiry_type', '')
            ->chunkById(1000, function ($users) {
                foreach ($users as $user) {
                    if ($user->membership_card_id > 0) {
                        $expiry_type = DB::table('user_membership_card')->where('id', $user->membership_card_id)->value('expiry_type');
                        DB::table('drp_shop')
                            ->where('id', $user->id)
                            ->update(['expiry_type' => $expiry_type]);
                    } else {
                        continue;
                    }
                }
            });
    }

    /**
     * @throws \Exception
     */
    private function shopConfig()
    {

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v8.8.8'
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
