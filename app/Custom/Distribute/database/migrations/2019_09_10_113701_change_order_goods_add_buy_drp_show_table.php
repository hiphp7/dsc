<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrderGoodsAddBuyDrpShowTable extends Migration
{
    protected $tableName = 'order_goods'; // 订单商品表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'buy_drp_show')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('buy_drp_show')->default(0)->comment('是否购买分销商品 0 否，1 是');
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
        if (Schema::hasColumn($this->tableName, 'buy_drp_show')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('buy_drp_show');
            });
        }
    }
}
