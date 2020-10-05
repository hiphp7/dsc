<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsAccountLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_account_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID');
            $table->decimal('user_money', 10)->default(0.00)->comment('可用资金');
            $table->decimal('frozen_money', 10)->default(0.00)->comment('冻结资金');
            $table->integer('change_time')->unsigned()->default(0)->comment('变动时间');
            $table->string('change_desc')->default('')->comment('变动原因');
            $table->boolean('change_type')->default(0)->index('change_type')->comment('变动类型（2：结算，3：充值，1/4：提现，5：冻结');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家余额变动日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_account_log');
    }
}
