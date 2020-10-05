<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOrderReturnAddReturnTradeDataTable extends Migration
{
    protected $tableName = 'order_return'; // 退换货订单表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'return_trade_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('return_trade_data')->default('')->comment('在线退款交易数据, 格式json');
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
        if (Schema::hasColumn($this->tableName, 'return_trade_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('return_trade_data');
            });
        }
    }
}
