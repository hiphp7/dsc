<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatMediaTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_media';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('title')->default('')->comment('图文消息标题');
            $table->string('command')->default('')->comment('关键词');
            $table->string('author')->default('')->comment('作者');
            $table->boolean('is_show')->default(0)->comment('是否显示封面，1为显示，0为不显示');
            $table->string('digest')->default('')->comment('图文消息的描述');
            $table->text('content')->nullable()->comment('图文消息页面的内容，支持HTML标签');
            $table->string('link')->default('')->comment('点击图文消息跳转链接');
            $table->string('file')->default('')->comment('图片链接');
            $table->integer('size')->unsigned()->default(0)->comment('媒体文件上传后，获取时的唯一标识');
            $table->string('file_name')->default('')->comment('媒体文件上传时间戳');
            $table->string('thumb')->default('')->comment('缩略图');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('edit_time')->unsigned()->default(0)->comment('编辑时间');
            $table->string('type')->nullable()->default('')->comment('素材类型');
            $table->string('article_id')->nullable()->default('')->comment('素材ID');
            $table->integer('sort')->unsigned()->default(0)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通素材'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_media');
    }
}
