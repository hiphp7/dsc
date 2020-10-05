<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTouchAdTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'touch_ad';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('ad_id')->comment('自增ID');
            $table->integer('position_id')->unsigned()->default(0)->index('position_id')->comment('广告位置ID');
            $table->boolean('media_type')->default(0)->comment('流媒体类型');
            $table->string('ad_name', 60)->default('')->comment('广告名称');
            $table->string('ad_link')->default('')->comment('广告链接');
            $table->string('link_color', 60)->comment('专题数据内容，包括分类，商品等');
            $table->text('ad_code')->nullable()->comment('广告编码');
            $table->integer('start_time')->default(0)->comment('广告开始时间');
            $table->integer('end_time')->default(0)->comment('广告结束时间');
            $table->string('link_man', 60)->default('')->comment('广告联系人');
            $table->string('link_email', 60)->default('')->comment('链接邮箱');
            $table->string('link_phone', 60)->default('')->comment('链接电话');
            $table->integer('click_count')->unsigned()->default(0)->comment('点击数');
            $table->boolean('enabled')->default(1)->index('enabled')->comment('是否激活');
            $table->boolean('is_new')->default(0)->comment('是否最新');
            $table->boolean('is_hot')->default(0)->comment('是否热门');
            $table->boolean('is_best')->default(0)->comment('是否推荐');
            $table->integer('public_ruid')->unsigned()->default(0)->comment('店铺ID（店铺广告位）');
            $table->boolean('ad_type')->default(0)->comment('广告类型');
            $table->string('goods_name')->default('')->comment('商品名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'H5广告位'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('touch_ad');
    }
}
