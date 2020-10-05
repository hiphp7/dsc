<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatWallMsgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_wall_msg';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->integer('wall_id')->unsigned()->default(0)->comment('微信墙活动id');
            $table->integer('user_id')->unsigned()->default(0)->comment('用户编号');
            $table->text('content')->nullable()->comment('留言内容');
            $table->integer('addtime')->unsigned()->default(0)->comment('发送时间');
            $table->integer('checktime')->unsigned()->default(0)->comment('审核时间');
            $table->boolean('status')->default(0)->comment('消息审核状态:0未审核,1审核通过');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通微信墙消息记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_wall_msg');
    }
}
