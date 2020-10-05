<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateValueCardRecordTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'value_card_record';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rid')->comment('自增ID');
            $table->integer('vc_id')->index('vc_id')->comment('储值卡ID');
            $table->integer('order_id')->index('order_id')->comment('订单ID');
            $table->decimal('use_val', 10)->comment('使用金额');
            $table->decimal('vc_dis', 10)->default(0.00)->comment('折扣比例');
            $table->integer('add_val')->comment('充值金额');
            $table->integer('record_time')->comment('记录时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '储值卡使用记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('value_card_record');
    }
}
