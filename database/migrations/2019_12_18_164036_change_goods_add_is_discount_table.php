<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeGoodsAddIsDiscountTable extends Migration
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
        if (!Schema::hasColumn($this->tableName, 'is_discount')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('is_discount')->default(0)->comment('是否参与会员特价权益: 0 否，1 是');
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
        if (Schema::hasColumn($this->tableName, 'is_discount')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('is_discount');
            });
        }
    }
}
