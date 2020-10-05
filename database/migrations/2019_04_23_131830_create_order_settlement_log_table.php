<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderSettlementLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_settlement_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增id');
            $table->integer('order_id')->index('order_id')->default(0)->comment('订单ID');
            $table->integer('ru_id')->index('ru_id')->default(0)->comment('商家ID');
            $table->integer('is_settlement')->index('is_settlement')->default(0)->comment('是否结算');
            $table->decimal('actual_amount', 10, 2)->default(0)->comment('实际结算金额');
            $table->tinyInteger('type')->default(0)->comment('触发结算类型：1、订单结算 2、账单结算');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->integer('update_time')->default(0)->comment('更新时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $name . "` comment '账单结算记录（用于统计已计算和未结算金额）表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_settlement_log');
    }
}
