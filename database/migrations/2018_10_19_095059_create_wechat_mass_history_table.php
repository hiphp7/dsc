<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatMassHistoryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_mass_history';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->integer('media_id')->unsigned()->default(0)->index()->comment('素材id');
            $table->string('type')->default('')->comment('发送内容类型');
            $table->string('status')->default('')->comment('发送状态，对应微信通通知状态');
            $table->integer('send_time')->unsigned()->default(0)->comment('发送时间');
            $table->string('msg_id')->default('')->comment('微信端返回的消息ID');
            $table->integer('totalcount')->unsigned()->default(0)->comment('group_id下粉丝数或者openid_list中的粉丝数');
            $table->integer('filtercount')->unsigned()->default(0)->comment('过滤粉丝数');
            $table->integer('sentcount')->unsigned()->default(0)->comment('发送成功的粉丝数');
            $table->integer('errorcount')->unsigned()->default(0)->comment('发送失败的粉丝数');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通群发消息记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_mass_history');
    }
}
