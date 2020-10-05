<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGalleryAlbumTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'gallery_album';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('album_id')->comment('自增ID');
            $table->integer('parent_album_id')->unsigned()->default(0)->index('parent_album_id')->comment('父级id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('suppliers_id')->unsigned()->default(0)->index('suppliers_id')->comment('供应商ID');
            $table->string('album_mame', 60)->default('')->comment('相册名称');
            $table->string('album_cover')->default('')->comment('相册封面');
            $table->string('album_desc')->default('')->comment('相册描述');
            $table->boolean('sort_order')->default(50)->comment('排序');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品图片库'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gallery_album');
    }
}
