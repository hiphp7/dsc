<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleCatTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_cat';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID');
            $table->string('cat_name', 90)->default('')->comment('分类名称');
            $table->string('keywords')->default('')->comment('关键词');
            $table->string('cat_desc')->default('')->comment('分类描述');
            $table->boolean('show_in_nav')->default(0)->comment('是否显示导航栏');
            $table->string('style', 150)->default('')->comment('分类样式');
            $table->boolean('is_show')->default(0)->index('is_show')->comment('是否显示');
            $table->string('style_icon', 50)->default('other')->comment('分类样式icon');
            $table->string('cat_icon')->default('')->comment('分类图标');
            $table->text('pinyin_keyword')->nullable()->comment('拼音关键词');
            $table->string('cat_alias_name', 90)->default('')->comment('分类别名');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父级id');
            $table->boolean('sort_order')->default(50)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_cat');
    }
}
