<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeGoodsAddDisCommissionTypeTable extends Migration
{
    protected $tableName = 'goods'; // 商品表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'dis_commission_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('dis_commission_type')->default(0)->comment('分销佣金值类型 0 百分比，1 数值');
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
        if (Schema::hasColumn($this->tableName, 'dis_commission_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('dis_commission_type');
            });
        }
    }
}
