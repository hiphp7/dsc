<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatPointTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_point';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('log_id')->unsigned()->default(0)->index()->comment('积分增加记录id');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('openid')->default('')->comment('微信用户openid');
            $table->string('keywords')->default('')->comment('关键词');
            $table->integer('createtime')->unsigned()->default(0)->comment('增加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通签到送积分记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_point');
    }
}
