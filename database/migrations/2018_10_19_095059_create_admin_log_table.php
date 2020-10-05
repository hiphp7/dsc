<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'admin_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增ID号');
            $table->integer('log_time')->unsigned()->default(0)->index('log_time')->comment('写日志时间');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('该日志所记录的操作者id，同ecs_admin_user的user_id');
            $table->string('log_info')->default('')->comment('管理操作内容');
            $table->string('ip_address', 15)->default('')->comment('管理者登录ip');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '管理员操作日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_log');
    }
}
