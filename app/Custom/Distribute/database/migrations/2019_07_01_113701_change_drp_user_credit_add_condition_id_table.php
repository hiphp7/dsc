<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpUserCreditAddConditionIdTable extends Migration
{
    protected $tableName = 'drp_user_credit'; // 分销商等级表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'condition_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('condition_id')->comment('分销商升级配置记录');
            });
        }
    }

    /**
     * 回滚数据库迁移
     *
     * @return void
     */
    public function down()
    {
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'condition_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('condition_id');
            });
        }
    }
}
