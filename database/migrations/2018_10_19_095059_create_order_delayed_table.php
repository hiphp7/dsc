<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderDelayedTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_delayed';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('delayed_id')->comment('自增ID号');
            $table->integer('order_id')->unsigned()->index('order_id')->comment('订单id');
            $table->boolean('apply_day')->comment('申请天数');
            $table->integer('apply_time')->unsigned()->comment('申请时间');
            $table->boolean('review_status')->comment('审核状态');
            $table->integer('review_time')->unsigned()->comment('审核时间');
            $table->integer('review_admin')->unsigned()->comment('管理员');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '订单延时收货申请列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_delayed');
    }
}
