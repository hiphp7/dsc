<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTouchPageViewTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'touch_page_view';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->string('type', 60)->default('1')->comment('店铺或专题');
            $table->integer('page_id')->unsigned()->default(0)->comment('店铺ID或专题ID');
            $table->string('title')->nullable()->comment('标题');
            $table->string('keywords')->nullable()->comment('关键字');
            $table->string('description')->nullable()->comment('描述');
            $table->text('data')->nullable()->comment('内容');
            $table->text('pic')->nullable()->comment('图片');
            $table->string('thumb_pic')->default('')->comment('缩略图');
            $table->integer('create_at')->unsigned()->nullable()->default(0)->comment('创建时间');
            $table->integer('update_at')->unsigned()->nullable()->default(0)->comment('更新时间');
            $table->integer('default')->unsigned()->default(0)->comment('数据 0 自定义数据 1 默认数据');
            $table->boolean('review_status')->default(1)->comment('审核状态1 3 ');
            $table->boolean('is_show')->default(1)->comment('是否显示 0 1');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'H5可视化页面'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('touch_page_view');
    }
}
