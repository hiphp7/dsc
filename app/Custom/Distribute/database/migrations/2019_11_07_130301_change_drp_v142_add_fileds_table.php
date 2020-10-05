<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpV142AddFiledsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = 'drp_account_log'; // 分销商记录表
        // 判断字段是否存在添加
        if (!Schema::hasColumn($tableName, 'log_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('log_id')->unsigned()->default(0)->index()->comment('支付日志id,关联pay_log表');
            });
        }
        if (!Schema::hasColumn($tableName, 'drp_is_separate')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('drp_is_separate')->unsigned()->default(0)->comment('分成状态：0 未分成、1 已分成');
            });
        }
        if (!Schema::hasColumn($tableName, 'parent_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('parent_id')->unsigned()->default(0)->comment('推荐人id 关联会员表');
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
        $tableName = 'drp_account_log'; // 分销商记录表
        // 删除字段
        if (Schema::hasColumn($tableName, 'log_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('log_id');
            });
        }
        if (Schema::hasColumn($tableName, 'drp_is_separate')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('drp_is_separate');
            });
        }
        if (Schema::hasColumn($tableName, 'parent_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('parent_id');
            });
        }
    }
}
