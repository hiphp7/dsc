<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerAccountLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_account_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员ID');
            $table->integer('real_id')->unsigned()->default(0)->index('real_id')->comment('实名认证ID');
            $table->integer('ru_id')->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->decimal('amount', 10)->default(0.00)->comment('商家账户金额');
            $table->decimal('frozen_money', 10)->default(0.00)->comment('冻结资金');
            $table->string('certificate_img')->default('')->comment('认证图片');
            $table->boolean('deposit_mode')->default(0)->comment('订金模式');
            $table->boolean('log_type')->default(0)->index('log_type')->comment('操作类型(1/4:提现 2:结算 3:充值)');
            $table->string('apply_sn', 225)->default('')->comment('申请序列号');
            $table->integer('pay_id')->unsigned()->default(0)->index('pay_id')->comment('付款方式ID');
            $table->integer('pay_time')->unsigned()->default(0)->comment('付款时间');
            $table->string('admin_note', 225)->default('')->comment('管理员回复信息');
            $table->integer('add_time')->unsigned()->default(0)->index('add_time')->comment('添加时间');
            $table->string('seller_note', 225)->default('')->comment('操作描述');
            $table->boolean('is_paid')->default(0)->index('is_paid')->comment('是否付款');
            $table->string('percent_value', 20)->default('')->comment('百分比值');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家资金账户日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_account_log');
    }
}
