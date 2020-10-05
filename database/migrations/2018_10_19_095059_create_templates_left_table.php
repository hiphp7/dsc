<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesLeftTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'templates_left';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('ru_id')->default(0)->index()->comment('商家ID');
            $table->string('seller_templates', 160)->default('')->comment('可视化模板文件夹名称');
            $table->char('bg_color', 10)->default('')->comment('商家模板背景颜色');
            $table->string('img_file', 120)->default('')->comment('图片文件');
            $table->boolean('if_show')->default(0)->comment('是否显示');
            $table->string('bgrepeat', 50)->default('')->comment('重置');
            $table->string('align', 50)->default('')->comment('对齐');
            $table->string('type', 20)->default('')->comment('类型');
            $table->string('theme', 60)->default('')->comment('商城模板名称');
            $table->string('fileurl')->default('')->comment('链接');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '可视化模板页面左侧背景参数'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('templates_left');
    }
}
