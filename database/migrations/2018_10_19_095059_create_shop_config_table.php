<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShopConfigTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'shop_config';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父节点id，取值于该表id字段的值');
            $table->string('code', 30)->default('')->unique('code')->comment('配置code');
            $table->string('type', 10)->default('')->comment('配置类型：text、select、file、hidden等');
            $table->string('store_range')->default('')->comment('配置数组索引');
            $table->string('store_dir')->default('')->comment('当type为file时才有值，文件上传后的保存目录');
            $table->text('value')->nullable()->comment('该项配置的值');
            $table->boolean('sort_order')->default(1)->comment('排序');
            $table->string('shop_group', 250)->default('')->comment('分组标识');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商城基本配置信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shop_config');
    }
}
