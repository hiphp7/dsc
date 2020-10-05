<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTemplateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'template';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('tid')->comment('自增ID');
            $table->string('filename', 30)->default('')->comment('该条模板配置属于哪个模板页面');
            $table->string('region', 40)->default('')->comment('该条模板配置在它所属的模板文件中的位置');
            $table->string('library', 40)->default('')->comment('该条模板配置在它所属的模板文件中的位置处应该引入的lib的相对目录地址');
            $table->boolean('sort_order')->default(0)->comment('模板文件中这个位置的引入lib项的值的显示顺序');
            $table->integer('id')->unsigned()->default(0)->comment('字段意义待查');
            $table->boolean('number')->default(5)->comment('每次显示多少个值');
            $table->boolean('type')->default(0)->index('type')->comment('属于哪个动态项，0，固定项；1，分类下的商品；2，品牌下的商品；3，文章列表；4，广告位');
            $table->string('theme', 60)->default('')->index('theme')->comment('该模板配置项属于哪套模板的模板名');
            $table->string('remarks', 30)->default('')->index('remarks')->comment('备注，可能是预留字段，没有值所以没确定用途');
            $table->smallInteger('floor_tpl')->default(0)->comment('首页楼层模板');
            $table->index(['filename', 'region'], 'filename');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商城模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('template');
    }
}
