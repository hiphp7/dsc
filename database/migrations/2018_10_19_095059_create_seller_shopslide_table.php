<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerShopslideTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_shopslide';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('主键id');
            $table->integer('ru_id')->default(0)->index('ru_id')->comment('入驻商家id');
            $table->string('img_url', 100)->default('')->comment('图片地址');
            $table->string('img_link', 100)->default('')->comment('图片超链接');
            $table->string('img_desc', 50)->default('')->comment('图片描述');
            $table->smallInteger('img_order')->default(0)->comment('排序');
            $table->string('slide_type', 50)->default('roll')->comment('图片变换方式 roll、shade,默认是roll');
            $table->boolean('is_show')->default(0)->comment('是否显示');
            $table->string('seller_theme', 20)->default('')->comment('商家模板');
            $table->boolean('install_img')->default(0)->comment('图片变换方式');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '店铺轮播图'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_shopslide');
    }
}
