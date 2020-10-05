<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpShopAddFieldsApplyTable extends Migration
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
        if (!Schema::hasColumn($this->tableName, 'apply_channel')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('apply_channel')->default(0)->comment('申请通道: 0,免费申请 1,购买商品 2,消费金额 3,消费积分 4,指定金额购买');
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
        if (Schema::hasColumn($this->tableName, 'apply_channel')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('apply_channel');
            });
        }
    }
}
