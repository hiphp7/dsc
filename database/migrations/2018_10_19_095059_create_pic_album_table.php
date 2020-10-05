<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePicAlbumTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'pic_album';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('pic_id')->comment('自增ID号');
            $table->string('pic_name', 100)->comment('图片名称');
            $table->integer('album_id')->unsigned()->index('album_id')->comment('相册ID');
            $table->string('pic_file')->comment('文件');
            $table->string('pic_thumb')->comment('图片缩略图');
            $table->string('pic_image')->comment('原图');
            $table->integer('pic_size')->unsigned()->comment('图片大小');
            $table->string('pic_spec', 100)->comment('图片尺寸（宽 * 高）');
            $table->integer('ru_id')->unsigned()->index('ru_id')->comment('商家ID');
            $table->integer('add_time')->unsigned()->comment('新增时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '图片库图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pic_album');
    }
}
