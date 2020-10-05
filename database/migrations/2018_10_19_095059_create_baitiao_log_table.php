<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBaitiaoLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'baitiao_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID号');
            $table->integer('baitiao_id')->default(0)->comment('白条id');
            $table->integer('user_id')->default(0)->comment('用户id');
            $table->string('use_date', 50)->default('')->comment('记账日期');
            $table->text('repay_date')->comment('还款日期');
            $table->integer('order_id')->default(0)->comment('订单id');
            $table->string('repayed_date', 50)->default('')->comment('完成支付日期');
            $table->boolean('is_repay')->default(0)->comment('是否还款');
            $table->boolean('is_stages')->default(0)->comment('是否为白条分期商品 1:分期 0:不分期');
            $table->boolean('stages_total')->default(0)->comment('当前订单的分期总期数');
            $table->decimal('stages_one_price', 10)->default(0.00)->comment('每期金额');
            $table->boolean('yes_num')->default(0)->comment('已还期数');
            $table->boolean('is_refund')->default(0)->comment('该白条记录对应的订单是否退款了. 1:退款 0:正常;');
            $table->boolean('pay_num')->default(0)->index('pay_num')->comment('已支付的期数');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '白条使用日志记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('baitiao_log');
    }
}
