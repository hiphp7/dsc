<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateConnectUserTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'connect_user';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->char('connect_code', 30)->comment('登录插件名sns_qq，sns_wechat');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->boolean('is_admin')->default(0)->comment('是否管理员,0是会员 ,1是管理员');
            $table->char('open_id', 64)->default('')->comment('标识');
            $table->char('refresh_token', 64)->nullable()->default('')->comment('微信refresh_token');
            $table->char('access_token', 64)->default('')->comment('微信access_token');
            $table->text('profile')->nullable()->comment('序列化用户信息');
            $table->integer('create_at')->unsigned()->default(0)->comment('创建时间');
            $table->integer('expires_in')->unsigned()->default(0)->comment('token过期时间');
            $table->integer('expires_at')->unsigned()->default(0)->comment('token保存时间');
            $table->index(['connect_code', 'open_id'], 'open_id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '第三方登录（APP、H5）'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('connect_user');
    }
}
