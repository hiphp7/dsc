<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name')->default('')->comment('公众号名称');
            $table->string('orgid')->default('')->comment('公众号原始ID');
            $table->string('weixin')->default('')->comment('微信号');
            $table->string('token')->default('')->comment('Token');
            $table->string('appid')->default('')->comment('AppID');
            $table->string('appsecret')->default('')->comment('AppSecret');
            $table->string('encodingaeskey')->default('')->comment('EncodingAESKey');
            $table->boolean('type')->default(0)->comment('公众号类型');
            $table->boolean('oauth_status')->default(0)->comment('是否开启微信登录');
            $table->string('secret_key')->default('')->comment('密钥');
            $table->string('oauth_redirecturi')->default('')->comment('回调地址');
            $table->integer('oauth_count')->unsigned()->default(0)->comment('回调统计');
            $table->integer('time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('sort')->unsigned()->default(0)->comment('排序');
            $table->boolean('status')->default(0)->comment('状态');
            $table->boolean('default_wx')->default(0)->comment('1为平台标识，0为商家标识');
            $table->integer('ru_id')->unsigned()->default(0)->unique()->comment('商家ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通公众号配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat');
    }
}
