<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSnatchLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'snatch_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('snatch_id')->unsigned()->default(0)->index('snatch_id')->comment('夺宝奇兵活动号，取值于dsc_goods_activity的act_id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('出价的用户id，取值于dsc_users的user_id');
            $table->decimal('bid_price', 10)->default(0.00)->comment('出价的价格');
            $table->integer('bid_time')->unsigned()->default(0)->comment('出价的时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '夺宝奇兵出价记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('snatch_log');
    }
}
