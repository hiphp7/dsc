<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQrpayLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'qrpay_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('pay_order_sn', 100)->default('')->unique()->comment('收款订单号');
            $table->decimal('pay_amount', 10)->default(0.00)->comment('收款金额');
            $table->integer('qrpay_id')->unsigned()->default(0)->comment('关联收款码id');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->integer('pay_user_id')->unsigned()->default(0)->comment('支付用户id');
            $table->string('openid')->default('')->comment('微信用户openid');
            $table->string('payment_code')->default('')->comment('支付方式');
            $table->string('trade_no')->default('')->comment('支付交易号');
            $table->text('notify_data')->comment('交易数据');
            $table->boolean('pay_status')->default(0)->comment('是否支付：0未支付 1已支付 ');
            $table->boolean('is_settlement')->default(0)->comment('是否结算：0未结算 1已结算 ');
            $table->string('pay_desc')->default('')->comment('备注');
            $table->integer('add_time')->unsigned()->default(0)->comment('记录时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '扫码支付日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('qrpay_log');
    }
}
