<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersAccountLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'suppliers_account_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('供货商ID【suppliers_id】');
            $table->decimal('user_money', 10, 2)->default(0.00)->comment('用户资金');
            $table->decimal('frozen_money', 10, 2)->default(0.00)->comment('冻结资金');
            $table->integer('change_time')->unsigned()->comment('帐户变动时间');
            $table->string('change_desc')->comment('变动说明');
            $table->boolean('change_type')->default(0)->comment('帐号变动类型【0：帐户冲值，1：帐户提款，2：调节帐户，3：分销分成，4：佣金转到余额，99：其他类型】');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '供应商账户记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('suppliers_account_log');
    }

}
