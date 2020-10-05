<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdsenseTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'adsense';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create('adsense', function (Blueprint $table) {
            $table->increments('id')->comment('自增id');
            $table->integer('from_ad')->default(0)->index('from_ad')->comment('广告代号，-1是站外广告，如果是站内广告则为ecs_ad的ad_id');
            $table->string('referer')->default('')->comment('页面来源');
            $table->integer('clicks')->unsigned()->default(0)->comment('点击率');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '广告点击率统计'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('adsense');
    }
}
