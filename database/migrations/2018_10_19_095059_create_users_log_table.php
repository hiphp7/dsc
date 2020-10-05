<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create('users_log', function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员id');
            $table->integer('change_time')->default(0)->comment('变动时间');
            $table->boolean('change_type')->default(0)->comment('变动类型');
            $table->string('ip_address', 15)->comment('ip地址');
            $table->string('change_city')->comment('变动城市');
            $table->string('logon_service', 60)->default('pc')->comment('登陆业务');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '管理员操作会员信息日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_log');
    }
}
