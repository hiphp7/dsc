<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatWallUserTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_wall_user';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->integer('wall_id')->unsigned()->default(0)->index()->comment('微信墙活动id');
            $table->string('nickname')->default('')->comment('用户昵称');
            $table->boolean('sex')->default(0)->comment('性别:1男,2女,0保密');
            $table->string('headimg')->default('')->comment('头像');
            $table->boolean('status')->default(0)->comment('用户审核状态:0未审核,1审核通过');
            $table->integer('addtime')->unsigned()->default(0)->comment('添加时间');
            $table->integer('checktime')->unsigned()->default(0)->comment('审核时间');
            $table->string('openid')->default('')->index()->comment('微信用户openid');
            $table->string('wechatname')->default('')->comment('微信用户昵称');
            $table->string('headimgurl')->default('')->comment('微信用户头像');
            $table->string('sign_number')->default('')->comment('号码');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通微信墙粉丝信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_wall_user');
    }
}
