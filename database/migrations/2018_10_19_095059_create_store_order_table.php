<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStoreOrderTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'store_order';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单ID');
            $table->integer('store_id')->unsigned()->default(0)->index('store_id')->comment('门店ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->boolean('is_grab_order')->default(0)->comment('抢单');
            $table->text('grab_store_list')->nullable()->comment('抢单门店列表');
            $table->string('pick_code', 25)->default('')->comment('提货码');
            $table->dateTime('take_time')->default('1000-01-01 00:00:00')->comment('提货时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '门店订单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('store_order');
    }
}
