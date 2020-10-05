<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsCategoryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_category';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID');
            $table->string('cat_name', 90)->default('')->index('cat_name')->comment('分类名称');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父级分类ID');
            $table->boolean('is_show')->default(0)->index('is_show')->comment('是否显示');
            $table->integer('user_id')->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('keywords')->default('')->comment('关键字');
            $table->string('cat_desc')->default('')->comment('分类描述');
            $table->integer('sort_order')->unsigned()->default(0)->index('sort_order')->comment('排序');
            $table->string('measure_unit', 15)->default('')->comment('单位');
            $table->boolean('show_in_nav')->default(0)->index('show_in_nav')->comment(' 是否导航栏显示');
            $table->string('style', 150)->default('')->comment('样式');
            $table->boolean('grade')->default(0)->comment('等级');
            $table->string('filter_attr', 225)->default('')->comment('过滤属性');
            $table->boolean('is_top_style')->default(0)->comment('是否使用顶部样式');
            $table->string('top_style_tpl')->default('')->comment('顶部样式模板');
            $table->string('cat_icon')->default('')->comment('分类图标');
            $table->boolean('is_top_show')->default(0)->index('is_top_show')->comment('是否顶部显示');
            $table->text('category_links')->comment('分类关联');
            $table->text('category_topic')->comment('分类专题');
            $table->text('pinyin_keyword')->comment('拼音关键字');
            $table->string('cat_alias_name', 90)->default('')->comment('分类别名');
            $table->string('template_file', 50)->default('')->comment('模板文件');
            $table->integer('add_titme')->default(0)->comment('添加时间');
            $table->string('touch_icon')->default('')->comment('手机小图标');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺商品分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_category');
    }
}
