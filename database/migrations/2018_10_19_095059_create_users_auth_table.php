<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersAuthTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users_auth';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->string('user_name', 60)->index('user_name')->comment('用户名称');
            $table->string('identity_type', 32)->comment('证件类型');
            $table->string('identifier', 128)->index('identifier')->comment('标识');
            $table->string('credential', 128)->comment('证书');
            $table->boolean('verified')->default(0)->comment('已验证');
            $table->integer('add_time')->unsigned()->default(0)->comment('新增时间');
            $table->integer('update_time')->default(0)->comment('更新时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '第三方登录用户信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_auth');
    }
}
