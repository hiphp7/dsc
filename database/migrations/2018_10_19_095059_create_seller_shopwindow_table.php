<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerShopwindowTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_shopwindow';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->boolean('win_type')->default(0)->comment('橱窗类型0商品列表，1自定义内容');
            $table->boolean('win_goods_type')->default(1)->comment('店铺橱窗类型');
            $table->smallInteger('win_order')->default(0)->comment('橱窗排序');
            $table->text('win_goods')->nullable()->comment('橱窗商品');
            $table->string('win_name', 50)->default('')->comment('橱窗名称');
            $table->char('win_color', 10)->default('')->comment('橱窗色调');
            $table->string('win_img', 100)->default('')->comment('橱窗广告图片，暂时无用');
            $table->string('win_img_link', 100)->default('')->comment('广告图片链接，暂时无用');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('入驻商id');
            $table->boolean('is_show')->default(0)->comment('是否显示');
            $table->text('win_custom')->comment('店铺自定义橱窗内容');
            $table->string('seller_theme', 20)->default('')->comment('店铺模板');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺装修橱窗'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_shopwindow');
    }
}
