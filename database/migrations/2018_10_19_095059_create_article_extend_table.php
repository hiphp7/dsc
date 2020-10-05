<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateArticleExtendTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'article_extend';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->integer('article_id')->unsigned()->default(0)->index('article_id')->comment('文章ID');
            $table->integer('click')->unsigned()->default(0)->comment('点击数');
            $table->integer('likenum')->unsigned()->default(0)->comment('顶');
            $table->integer('hatenum')->unsigned()->default(0)->comment('踩');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '文章扩展信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('article_extend');
    }
}
