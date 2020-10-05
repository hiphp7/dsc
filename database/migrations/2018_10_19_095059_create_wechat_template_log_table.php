<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatTemplateLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_template_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->bigInteger('msgid')->unsigned()->default(0)->comment('微信消息ID');
            $table->string('code')->default('')->comment('模板消息标识');
            $table->string('openid')->default('')->index()->comment('微信用户openid');
            $table->text('data')->nullable()->comment('消息数据');
            $table->string('url')->default('')->comment('消息链接地址');
            $table->boolean('status')->default(0)->comment('状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通模板消息发送记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_template_log');
    }
}
