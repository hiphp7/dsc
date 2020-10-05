<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerQrcodeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_qrcode';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('qrcode_id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('qrcode_thumb')->default('')->comment('上传二维码图片');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺二维码图片管理'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_qrcode');
    }
}
