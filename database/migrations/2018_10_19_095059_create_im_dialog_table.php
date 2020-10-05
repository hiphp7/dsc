<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateImDialogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'im_dialog';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('customer_id')->unsigned()->default(0)->comment('客户ID');
            $table->integer('services_id')->unsigned()->default(0)->comment('客服ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->integer('store_id')->unsigned()->default(0)->comment('商家ID');
            $table->integer('start_time')->unsigned()->default(0)->comment('开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('结束时间');
            $table->boolean('origin')->default(0)->comment('1-PC 2-phone');
            $table->boolean('status')->default(1)->comment('1-未结束  2-已结束');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '客服会话'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('im_dialog');
    }
}
