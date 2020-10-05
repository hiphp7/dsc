<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCategoryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'category';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID');
            $table->string('cat_name', 90)->default('')->index('cat_name')->comment('分类名称');
            $table->string('keywords')->default('')->comment('关键字');
            $table->string('cat_desc')->default('')->comment('分类描述');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('该id项的父id，对应本表的cat_id字段');
            $table->integer('sort_order')->unsigned()->default(50)->comment('排序');
            $table->string('template_file', 50)->default('')->comment('商品分类页模板');
            $table->string('measure_unit', 15)->default('')->comment('单位');
            $table->boolean('show_in_nav')->default(0)->index('show_in_nav')->comment('是否显示导航栏');
            $table->string('style', 150)->comment('分类的样式表文件');
            $table->boolean('is_show')->default(1)->index('is_show')->comment('是否显示');
            $table->boolean('grade')->default(0)->index('grade')->comment('价格区间个数');
            $table->string('filter_attr')->default('0')->comment('筛选属性');
            $table->boolean('is_top_style')->default(0)->index('is_top_style')->comment('是否使用顶级分类页样式');
            $table->string('top_style_tpl')->index('top_style_tpl')->comment('顶级分类页模板');
            $table->string('style_icon', 50)->default('other')->comment('分类菜单图标库类');
            $table->string('cat_icon')->comment('顶级分类页菜单图标');
            $table->string('touch_catads')->comment('手机分类列表广告图');
            $table->boolean('is_top_show')->default(0)->index('is_top_show')->comment('是否使用顶级分类页样式');
            $table->text('category_links')->comment('分类跳转链接');
            $table->text('category_topic')->comment('分类树顶级分类模块内容');
            $table->text('pinyin_keyword')->comment('分类名称转换拼音');
            $table->string('cat_alias_name', 90)->comment('手机别名');
            $table->integer('commission_rate')->unsigned()->default(0)->comment('分类佣金比例');
            $table->string('touch_icon')->comment('手机小图标');
            $table->string('cate_title', 200)->default('')->comment('关键词');
            $table->string('cate_keywords')->default('')->comment('关键词');
            $table->string('cate_description')->default('')->comment('描述');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('category');
    }
}
