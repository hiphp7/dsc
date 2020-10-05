<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePayLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'pay_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->decimal('order_amount', 10)->unsigned()->comment('支付金额');
            $table->boolean('order_type')->default(0)->comment('支付类型；0，订单支付；1，会员预付款支付');
            $table->boolean('is_paid')->default(0)->index('is_paid')->comment('是否已支付，0，否；1，是');
            $table->string('openid')->comment('微信粉丝openid');
            $table->string('transid')->comment('微信支付交易订单号');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '支付日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pay_log');
    }
}
