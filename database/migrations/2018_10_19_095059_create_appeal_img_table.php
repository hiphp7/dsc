<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAppealImgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'appeal_img';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('img_id')->comment('自增ID号');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单id');
            $table->integer('complaint_id')->unsigned()->default(0)->index('complaint_id')->comment('投诉id  来自complaint自增id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家id');
            $table->string('img_file')->default('')->comment('图片链接');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '交易纠纷申诉图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('appeal_img');
    }
}
