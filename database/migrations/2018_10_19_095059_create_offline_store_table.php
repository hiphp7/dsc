<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOfflineStoreTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'offline_store';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('ru_id')->default(0)->index('ru_id')->comment('商户ID');
            $table->string('stores_user', 60)->default('')->index('stores_user')->comment('门店登录名');
            $table->string('stores_pwd', 32)->comment('登录密码');
            $table->string('stores_name', 60)->default('')->comment('门店名称');
            $table->smallInteger('country')->default(0)->index('country')->comment('国家ID');
            $table->smallInteger('province')->default(0)->index('province')->comment('省份ID');
            $table->smallInteger('city')->default(0)->index('city')->comment(' 城市ID');
            $table->smallInteger('district')->default(0)->index('district')->comment('地区ID');
            $table->smallInteger('street')->default(0)->index('street')->comment('街道ID');
            $table->string('stores_address')->default('')->comment('详细地址');
            $table->string('stores_tel', 60)->default('')->comment('门店电话');
            $table->string('stores_opening_hours')->default('')->comment('营业时间');
            $table->string('stores_traffic_line')->default('')->comment('交通路线');
            $table->string('stores_img')->default('')->comment('实景图片');
            $table->boolean('is_confirm')->default(1)->index('is_confirm')->comment('门店状态（1、开始，0、关闭）');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->string('ec_salt', 10)->default('')->index('ec_salt')->comment('密码加密扩展');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '线下门店'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('offline_store');
    }
}
