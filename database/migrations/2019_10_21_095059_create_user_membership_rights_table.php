<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserMembershipRightsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_membership_rights';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 60)->comment('权益名称');
            $table->string('code', 60)->comment('权益code');
            $table->string('description')->default('')->comment('权益说明');
            $table->string('icon', 120)->default('')->comment('权益图标');
            $table->text('rights_configure')->comment('权益配置,序列化');
            $table->string('trigger_point', 30)->default('direct')->comment('触发方式: direct(直接),manual(手动),scheduled(定时)');
            $table->string('trigger_configure')->default('')->comment('触发配置');
            $table->integer('enable')->unsigned()->default(0)->comment('权益状态：0 关闭 1 开启');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
            $table->integer('sort')->unsigned()->default(50)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员权益表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_membership_rights');
    }
}
