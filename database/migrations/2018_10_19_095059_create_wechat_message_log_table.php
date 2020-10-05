<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatMessageLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_message_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('fromusername')->default('')->comment('发送方帐号openid');
            $table->integer('createtime')->unsigned()->default(0)->comment('消息创建时间');
            $table->string('keywords')->default('')->comment('微信消息内容');
            $table->string('msgtype')->default('')->comment('微信消息类型');
            $table->bigInteger('msgid')->unsigned()->default(0)->comment('微信消息ID');
            $table->boolean('is_send')->default(0)->comment('发送状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通消息队列日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_message_log');
    }
}
