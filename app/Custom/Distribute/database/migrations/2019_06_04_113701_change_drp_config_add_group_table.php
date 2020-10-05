<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpConfigAddGroupTable extends Migration
{
    protected $tableName = 'drp_config'; // 分销配置表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'group')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('group')->default('')->comment('配置分组');
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
        if (Schema::hasColumn($this->tableName, 'group')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('group');
            });
        }
    }
}
