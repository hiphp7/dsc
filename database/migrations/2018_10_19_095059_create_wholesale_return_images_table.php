<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleReturnImagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_return_images';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->smallInteger('rg_id')->unsigned()->default(0)->index('rg_id')->comment('退换货商品ID');
            $table->integer('rec_id')->index('rec_id')->comment('订单商品ID');
            $table->integer('user_id')->index('user_id')->comment('会员ID');
            $table->string('img_file')->comment('图片信息');
            $table->integer('add_time')->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '退换货上传凭证图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_return_images');
    }

}
