<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePresaleCatTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'presale_cat';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID号');
            $table->string('cat_name', 90)->index('cat_name')->comment('分类名称');
            $table->string('keywords')->comment('关键字');
            $table->string('cat_desc')->comment('分类描述');
            $table->string('measure_unit', 15)->comment('计量单位');
            $table->boolean('show_in_nav')->default(0)->comment('是否在头部显示');
            $table->string('style', 150)->comment('风格类型');
            $table->boolean('is_show')->default(0)->index('is_show')->comment('是否显示');
            $table->boolean('grade')->default(0)->comment('商家等级');
            $table->string('filter_attr', 225)->comment('过滤属性');
            $table->boolean('is_top_style')->default(0)->comment('是否使用顶部样式');
            $table->string('top_style_tpl')->comment('顶部样式模板');
            $table->string('cat_icon')->comment('分类图标');
            $table->boolean('is_top_show')->default(0)->comment('是否置顶显示');
            $table->text('category_links')->comment('分类关联');
            $table->text('category_topic')->comment('分类专题');
            $table->text('pinyin_keyword')->comment('拼音关键字');
            $table->string('cat_alias_name', 90)->comment('分类别名');
            $table->string('template_file', 50)->comment('模板文件');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父类ID');
            $table->boolean('sort_order')->default(50)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '预售分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('presale_cat');
    }
}
