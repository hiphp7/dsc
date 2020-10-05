<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpShopAddFieldsTable extends Migration
{
    protected $tableName = 'drp_shop'; // 分销商表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'frozen_money')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->decimal('frozen_money', 10)->default(0.00)->comment('冻结资金');
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
        if (Schema::hasColumn($this->tableName, 'frozen_money')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('frozen_money');
            });
        }
    }
}
