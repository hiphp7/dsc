<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAuctionLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'auction_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID号');
            $table->integer('act_id')->unsigned()->default(0)->index('act_id')->comment('拍卖活动的id，同goods_activity的act_id');
            $table->integer('bid_user')->unsigned()->default(0)->index('bid_user')->comment('出价的用户id，取值于users的user_id');
            $table->decimal('bid_price', 10)->unsigned()->comment('出价价格');
            $table->integer('bid_time')->unsigned()->default(0)->comment('出价时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '拍卖出价记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('auction_log');
    }
}
