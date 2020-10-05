<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePayLogAddPayTradeDataTable extends Migration
{
    protected $tableName = 'pay_log'; // 支付日志表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'pay_trade_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('pay_trade_data')->default('')->comment('在线支付交易数据, 格式json');
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
        if (Schema::hasColumn($this->tableName, 'pay_trade_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('pay_trade_data');
            });
        }
    }
}
