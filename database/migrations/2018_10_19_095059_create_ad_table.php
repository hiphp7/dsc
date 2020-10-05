<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'ad';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('ad_id')->comment('自增ID号');
            $table->integer('position_id')->unsigned()->default(0)->index('position_id')->comment('0,站外广告；从1开始代表的是该广告所处的广告位，同表ad_position中的字段position_id的值');
            $table->boolean('media_type')->default(0)->index('media_type')->comment('该条广告记录的广告名称');
            $table->string('ad_name', 60)->default('')->index('ad_name')->comment('该条广告记录的广告名称');
            $table->string('ad_link')->default('')->comment('广告链接地址');
            $table->string('link_color', 60)->default('')->comment('广告背景颜色');
            $table->string('b_title', 60)->default('')->comment('广告大标题');
            $table->string('s_title', 60)->default('')->comment('广告小标题');
            $table->text('ad_code')->comment('广告链接的表现，文字广告就是文字或图片和flash就是它们的地址，代码广告就是代码内容');
            $table->text('ad_bg_code')->comment('广告背景');
            $table->integer('start_time')->default(0)->index('start_time')->comment('广告开始时间');
            $table->integer('end_time')->default(0)->index('end_time')->comment('广告结束时间');
            $table->string('link_man', 60)->default('')->comment('广告联系人');
            $table->string('link_email', 60)->default('')->comment('广告联系人的邮箱');
            $table->string('link_phone', 60)->default('')->comment('广告联系人的电话');
            $table->integer('click_count')->unsigned()->default(0)->comment('该广告点击数');
            $table->boolean('enabled')->default(1)->comment('该广告是否关闭，1，开启；0，关闭');
            $table->boolean('is_new')->default(0)->comment('(该字段暂时无用)');
            $table->boolean('is_hot')->default(0)->comment('(该字段暂时无用)');
            $table->boolean('is_best')->default(0)->comment('(该字段暂时无用)');
            $table->integer('public_ruid')->unsigned()->default(0)->index()->comment('商家ID');
            $table->boolean('ad_type')->default(0)->comment('广告类型（0-广告， 1-商品）');
            $table->string('goods_name')->default('')->comment('商品名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '广告内容配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ad');
    }
}
