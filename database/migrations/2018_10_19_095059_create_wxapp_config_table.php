<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWxappConfigTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wxapp_config';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('wx_appname')->default('')->comment('小程序名称');
            $table->string('wx_appid')->default('')->comment('小程序AppID');
            $table->string('wx_appsecret')->default('')->comment('小程序AppSecret');
            $table->string('wx_mch_id')->default('')->comment('商户号');
            $table->string('wx_mch_key')->default('')->comment('支付密钥');
            $table->string('token_secret')->default('')->comment('Token授权加密key');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->boolean('status')->default(0)->comment('状态：0 关闭 1 开启');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '小程序基本配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wxapp_config');
    }
}
