<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAccountLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'account_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID号');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户登录后保存在session中的id号');
            $table->decimal('user_money', 10)->default(0.00)->comment('用户该笔记录的余额');
            $table->decimal('deposit_fee', 10)->default(0.00)->comment('提现手续费');
            $table->decimal('frozen_money', 10)->default(0.00)->comment('被冻结的资金');
            $table->integer('rank_points')->default(0)->index('rank_points')->comment('等级积分');
            $table->integer('pay_points')->default(0)->index('pay_points')->comment('消费积分');
            $table->integer('change_time')->unsigned()->default(0)->comment('该笔操作发生的时间');
            $table->string('change_desc')->default('')->comment('该笔操作的备注，一般是，充值或者提现。也可是是管理员后台写的任何在备注');
            $table->boolean('change_type')->default(0)->comment('操作类型，0为充值，1为提现，2为管理员调节，99为其他类型');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '用户帐号情况记录表，包括资金和积分等'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('account_log');
    }
}
