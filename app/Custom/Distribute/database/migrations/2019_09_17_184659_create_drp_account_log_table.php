<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDrpAccountLogTable extends Migration
{

    protected $tableName = 'drp_account_log'; // 分销商账户记录表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            return false;
        }
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户user_id');
            $table->string('admin_user')->default('')->comment('操作该笔交易的管理员的用户名');
            $table->decimal('amount', 10)->default(0.00)->comment('资金的数目，正数为增加，负数为减少');
            $table->integer('pay_points')->default(0)->comment('消费积分的值');
            $table->decimal('deposit_fee', 10)->default(0.00)->comment('手续费');
            $table->integer('add_time')->unsigned()->default(0)->comment('记录插入时间');
            $table->integer('paid_time')->unsigned()->default(0)->comment('记录更新时间');
            $table->string('admin_note')->default('')->comment('管理员的备注');
            $table->string('user_note')->default('')->comment('用户的备注');
            $table->tinyInteger('account_type')->default(0)->comment('操作类型，0 购买分销商; 1 消费积分兑换, 99 其他');
            $table->string('payment', 90)->default('')->comment('支付渠道的名称，取自dsc_payment的pay_name');
            $table->integer('pay_id')->unsigned()->default(0)->index('pay_id')->comment('支付ID');
            $table->tinyInteger('is_paid')->unsigned()->default(0)->comment('是否已经付款，0，未付；1，已付');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$this->tableName` comment '分销商账户记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
