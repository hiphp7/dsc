<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminUserTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'admin_user';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('user_id')->comment('自增ID号，管理员代号');
            $table->string('user_name', 60)->default('')->index('user_name')->comment('管理员登录名');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父节点id，取值于该表user_id字段');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id');
            $table->integer('rs_id')->unsigned()->default(0)->index('rs_id')->comment('卖场id（预留字段）');
            $table->string('email', 60)->default('')->comment('管理员邮箱');
            $table->string('password', 32)->default('')->comment('管理员登录秘密加密串');
            $table->string('ec_salt', 10)->nullable()->index('ec_salt')->comment('密码加密扩展');
            $table->integer('add_time')->default(0)->comment('管理员添加时间');
            $table->integer('last_login')->default(0)->comment('管理员最后一次登录时间');
            $table->string('last_ip', 15)->default('')->comment('管理员最后一次登录ip');
            $table->text('action_list')->comment('管理员管理权限列表');
            $table->text('nav_list')->comment('管理员导航栏配置项');
            $table->string('lang_type', 50)->default('')->comment('语言类型');
            $table->integer('agency_id')->unsigned()->default(0)->index('agency_id')->comment('该管理员负责的办事处的id，同agency的agency_id字段。如果管理员没负责办事处，则此处为0');
            $table->integer('suppliers_id')->unsigned()->nullable()->default(0)->comment('记事本记录的数据');
            $table->text('todolist')->nullable()->comment('待办事项');
            $table->smallInteger('role_id')->nullable()->index('role_id')->comment('角色id');
            $table->integer('major_brand')->unsigned()->default(0)->comment('(该字段暂时无用)');
            $table->string('admin_user_img')->default('')->comment('管理员头像');
            $table->string('recently_cat')->default('')->comment('管理员最近使用分类');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '管理员列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_user');
    }
}
