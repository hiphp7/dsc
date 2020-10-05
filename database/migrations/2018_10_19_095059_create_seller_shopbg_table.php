<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerShopbgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_shopbg';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('bgimg', 500)->default('')->comment('背景图片');
            $table->string('bgrepeat', 50)->default('no-repeat')->comment('背景图片重复');
            $table->string('bgcolor', 20)->default('')->comment('背景颜色');
            $table->boolean('show_img')->default(0)->comment('默认显示背景图片');
            $table->integer('is_custom')->default(0)->comment('是否自定义背景，默认为否');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家id');
            $table->string('seller_theme', 50)->default('')->comment('模板');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家店铺背景'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_shopbg');
    }
}
