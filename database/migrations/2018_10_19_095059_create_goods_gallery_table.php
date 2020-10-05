<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsGalleryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_gallery';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('img_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('商品id');
            $table->string('img_url')->default('')->comment('实际图片url');
            $table->smallInteger('img_desc')->default(100)->comment('图片说明信息');
            $table->string('thumb_url')->default('')->comment('缩略图片url');
            $table->string('img_original')->default('')->comment('原始图片');
            $table->integer('single_id')->nullable()->comment('晒单ID');
            $table->string('external_url')->comment('外链');
            $table->boolean('front_cover')->nullable()->comment('设为封面');
            $table->integer('dis_id')->nullable()->comment('讨论圈ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品相册'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_gallery');
    }
}
