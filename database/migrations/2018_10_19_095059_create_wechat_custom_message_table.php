<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatCustomMessageTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_custom_message';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->integer('uid')->unsigned()->default(0)->index()->comment('wechat_user表用户uid');
            $table->string('msg')->default('')->comment('信息内容');
            $table->integer('send_time')->unsigned()->default(0)->comment('发送时间');
            $table->boolean('is_wechat_admin')->default(0)->comment('是否管理员回复: 0否,1是');
            $table->string('msgtype')->default('')->comment('消息类型');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通消息记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_custom_message');
    }
}
