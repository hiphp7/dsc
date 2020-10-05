<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTouchTopicTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'touch_topic';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('topic_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->string('title')->default('')->comment('专题名称标题');
            $table->text('intro')->nullable()->comment('专题介绍');
            $table->integer('start_time')->default(0)->comment('开始时间');
            $table->integer('end_time')->default(0)->comment('结束时间');
            $table->text('data')->nullable()->comment('专题数据内容，包括分类，商品等');
            $table->string('template')->default('')->comment('专题模板文件');
            $table->text('css')->nullable()->comment('专题样式代码');
            $table->string('topic_img')->default('')->comment('专题图片');
            $table->string('title_pic')->default('')->comment('专题图片标题');
            $table->char('base_style', 6)->default('')->comment('基本风格样式');
            $table->text('htmls')->nullable()->comment('html内容');
            $table->string('keywords')->default('')->comment('关键词');
            $table->string('description')->default('')->comment('描述');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content', 1000)->default('')->comment('审核回复内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'H5专题'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('touch_topic');
    }
}
