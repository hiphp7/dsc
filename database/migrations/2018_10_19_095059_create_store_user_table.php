<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStoreUserTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'store_user';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('store_id')->unsigned()->default(0)->index('store_id')->comment('门店ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('上一级（父级）ID');
            $table->string('stores_user', 60)->default('')->comment('门店管理员账号');
            $table->string('stores_pwd', 32)->default('')->comment('门店管理员密码');
            $table->string('tel', 20)->default('')->comment('门店电话');
            $table->string('email', 60)->default('')->comment('门店邮箱');
            $table->text('store_action')->nullable()->comment('门店权限');
            $table->integer('add_time')->default(0)->comment('新增时间');
            $table->string('ec_salt', 10)->default('')->comment('密码加密扩展');
            $table->string('store_user_img')->default('')->comment('门店管理员头像');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺门店'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('store_user');
    }
}
