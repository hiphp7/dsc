<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsReportImgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_report_img';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('img_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品id');
            $table->integer('report_id')->unsigned()->default(0)->comment('举报id');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->string('img_file')->comment('图片地址');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品举报图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_report_img');
    }
}
