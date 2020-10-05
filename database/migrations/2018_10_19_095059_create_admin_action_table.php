<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminActionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'admin_action';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('action_id')->comment('自增ID号');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('该id项的父id，对应本表的action_id字段');
            $table->string('action_code', 20)->default('')->comment('代表权限的英文字符串，对应中文在语言文件中，如果该字段有某个字符串，就表示有该权限');
            $table->string('relevance', 20)->default('')->comment('关联权限');
            $table->boolean('seller_show')->default(1)->comment('商家可使用的权限 1，是；0，否');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '管理员权限配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_action');
    }
}
