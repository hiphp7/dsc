<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpV141AddFiledsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 删除字段
        $tableName = 'goods'; // 商品表
        if (Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_id');
            });
        }

        // 删除字段
        $tableName = 'order_goods'; // 订单商品表
        if (Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_id');
            });
        }

        $tableName = 'drp_shop';
        // 删除字段
        if (Schema::hasColumn($tableName, 'membership_status')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('membership_status');
            });
        }

        $tableName = 'drp_log';
        // 删除字段
        if (Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_id');
            });
        }
        if (Schema::hasColumn($tableName, 'log_type')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('log_type');
            });
        }

        $tableName = 'pay_log';
        // 删除字段
        if (Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_id');
            });
        }

        $tableName = 'drp_account_log'; // 分销商记录表
        // 删除字段
        if (Schema::hasColumn($tableName, 'membership_card_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_id');
            });
        }
        if (Schema::hasColumn($tableName, 'receive_type')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('receive_type');
            });
        }
    }
}
