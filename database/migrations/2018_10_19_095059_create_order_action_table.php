<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderActionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_action';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('action_id')->comment('自增ID号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->string('action_user', 30)->default('')->index('action_user')->comment('操作管理员');
            $table->boolean('order_status')->default(0)->index('order_status')->comment('订单状态');
            $table->boolean('shipping_status')->default(0)->index('shipping_status')->comment('配送状态');
            $table->boolean('pay_status')->default(0)->index('pay_status')->comment('付款状态');
            $table->boolean('action_place')->default(0)->comment('（取消订单记录，值为1）');
            $table->string('action_note')->default('')->comment('操作备注');
            $table->integer('log_time')->unsigned()->default(0)->comment('操作时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '订单操作记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_action');
    }
}
