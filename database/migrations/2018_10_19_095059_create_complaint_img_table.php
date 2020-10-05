<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateComplaintImgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'complaint_img';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('img_id')->comment('自增ID');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('订单id');
            $table->integer('complaint_id')->unsigned()->default(0)->index('complaint_id')->comment('投诉id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员id');
            $table->string('img_file')->comment('投诉图片');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员订单交易纠纷上传图片'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('complaint_img');
    }
}
