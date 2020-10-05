<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatUserTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_user';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('uid')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->boolean('subscribe')->default(0)->comment('用户是否订阅该公众号标识');
            $table->string('openid')->default('')->comment('用户公众平台唯一标识');
            $table->string('nickname')->default('')->comment('用户昵称');
            $table->boolean('sex')->default(0)->comment('用户性别');
            $table->string('city')->default('')->comment('用户所在城市');
            $table->string('country')->default('')->comment('用户所在国家');
            $table->string('province')->default('')->comment('用户所在省份');
            $table->string('language')->default('')->comment('语言');
            $table->string('headimgurl')->default('')->comment('用户头像');
            $table->integer('subscribe_time')->unsigned()->default(0)->comment('关注时间');
            $table->string('remark')->default('')->comment('备注');
            $table->string('privilege')->default('')->comment('其他信息');
            $table->string('unionid', 100)->default('')->unique()->comment('用户开放平台唯一标识');
            $table->integer('groupid')->unsigned()->default(0)->comment('用户组id');
            $table->integer('ect_uid')->unsigned()->default(0)->comment('会员id');
            $table->boolean('bein_kefu')->default(0)->comment('是否处在多客服流程');
            $table->integer('parent_id')->unsigned()->default(0)->comment('推荐人id');
            $table->boolean('drp_parent_id')->default(0)->comment('分销推荐user_id');
            $table->boolean('from')->default(0)->comment('粉丝来源：0 微信公众号关注 1 微信授权注册,2 微信扫码注册');
            $table->string('subscribe_scene')->default('')->comment('用户关注的渠道来源');
            $table->integer('qr_scene')->unsigned()->default(0)->comment('二维码扫码场景');
            $table->string('qr_scene_str')->default('')->comment('二维码扫码场景描述');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '微信通粉丝信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_user');
    }
}
