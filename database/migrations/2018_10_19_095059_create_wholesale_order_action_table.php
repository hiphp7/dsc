<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleOrderActionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_order_action';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('action_id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单id');
            $table->string('action_user', 30)->default('')->comment('操作员姓名');
            $table->boolean('order_status')->default(0)->comment('订单状态');
            $table->boolean('action_place')->default(0)->comment('（取消订单记录，值为1）');
            $table->string('action_note')->default('')->comment('操作说明');
            $table->integer('log_time')->unsigned()->default(0)->comment('操作时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发订单操作记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_order_action');
    }
}
