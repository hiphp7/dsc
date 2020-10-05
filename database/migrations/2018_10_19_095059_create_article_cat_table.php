<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateArticleCatTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'article_cat';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cat_id')->comment('自增ID号');
            $table->string('cat_name')->default('')->index('cat_name')->comment('分类名称');
            $table->boolean('cat_type')->default(1)->index('cat_type')->comment('分类类型；1，普通分类；2，系统分类；3，网店信息；4，帮助分类；5，网店帮助');
            $table->string('keywords')->default('')->comment('分类关键字');
            $table->string('cat_desc')->default('')->comment('分类说明文字');
            $table->boolean('sort_order')->default(50)->index('sort_order')->comment('分类显示顺序');
            $table->boolean('show_in_nav')->default(0)->comment('是否在导航栏显示；0，否；1，是');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父节点id，取值于该表cat_id字段');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '文章分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('article_cat');
    }
}
