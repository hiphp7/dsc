<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserAccountTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_account';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户登录后保存在session中的id号，跟dsc_users表中的user_id对应');
            $table->string('admin_user')->default('')->comment('操作该笔交易的管理员的用户名');
            $table->decimal('amount', 10, 2)->default(0.00)->comment('资金的数目，正数为增加，负数为减少');
            $table->decimal('deposit_fee', 10, 2)->default(0.00)->comment('手续费');
            $table->integer('add_time')->default(0)->comment('记录插入时间');
            $table->integer('paid_time')->default(0)->comment('记录更新时间');
            $table->string('admin_note')->default('')->comment('管理员的备注');
            $table->string('user_note')->default('')->comment('用户的备注');
            $table->boolean('process_type')->default(0)->comment('操作类型，1，退款；0，预付费，其实就是充值');
            $table->string('payment', 90)->default('')->comment('支付渠道的名称，取自dsc_payment的pay_name');
            $table->integer('pay_id')->unsigned()->default(0)->index('pay_id')->comment('支付ID');
            $table->boolean('is_paid')->default(0)->index('is_paid')->comment('是否已经付款，0，未付；1，已付');
            $table->string('complaint_details', 500)->default('')->comment('申诉内容');
            $table->string('complaint_imges')->default('')->comment('申诉照片');
            $table->integer('complaint_time')->unsigned()->default(0)->comment('申诉时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '用户资金流动'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_account');
    }
}
