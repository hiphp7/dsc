<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersGoodsGalleryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'suppliers_goods_gallery';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('img_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->string('img_url')->comment('图片地址');
            $table->string('img_desc')->comment('图片排序');
            $table->string('thumb_url')->comment('缩略图地址');
            $table->string('img_original')->comment('图片原图地址');
            $table->string('external_url')->comment('外链地址');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '供货商商品相册'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('suppliers_goods_gallery');
    }

}
