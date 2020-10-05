<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateZcCategoryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'zc_category';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('分类id');
            $table->string('cat_name')->default('')->comment('分类名称');
            $table->string('keywords')->default('')->comment('关键词');
            $table->string('measure_unit')->default('')->comment('单位');
            $table->boolean('show_in_nav')->default(0)->comment('是否显示在导航栏');
            $table->string('style')->default('')->comment('样式');
            $table->boolean('grade')->default(0)->comment('等级');
            $table->string('filter_attr')->default('')->comment('属性');
            $table->boolean('is_top_style')->default(0)->comment('是否应用顶级分类样式');
            $table->string('top_style_tpl')->default('')->comment('顶级分类样式模板');
            $table->string('cat_icon')->default('')->comment('分类图标');
            $table->boolean('is_top_show')->default(0)->comment('是否顶部显示');
            $table->string('category_links')->default('')->comment('分类关联');
            $table->string('category_topic')->default('')->comment('分类专题');
            $table->string('pinyin_keyword')->default('')->comment('拼音关键词');
            $table->string('cat_alias_name')->default('')->comment('分类别名');
            $table->string('template_file')->default('')->comment('模板文件');
            $table->string('cat_desc')->default('')->comment('分类描述');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父分类id');
            $table->boolean('sort_order')->default(50)->comment('排序');
            $table->boolean('is_show')->default(1)->comment('是否显示');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '众筹分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('zc_category');
    }
}
