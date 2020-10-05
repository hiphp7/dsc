<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturnImagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'return_images';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('rg_id')->unsigned()->default(0)->index('rg_id')->comment('同dsc_return_goods的rg_id');
            $table->integer('rec_id')->index('rec_id')->comment('订单商品ID');
            $table->integer('user_id')->index('user_id')->comment('会员ID');
            $table->string('img_file')->comment('图片文件');
            $table->integer('add_time')->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '退换货商品上传认证图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('return_images');
    }
}
