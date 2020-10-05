<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateArticleTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'article';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('article_id')->comment('自增ID号');
            $table->integer('cat_id')->default(0)->index('cat_id')->comment('该文章的分类，同dsc_article_cat的cat_id,如果不在，将自动成为保留类型而不能删除');
            $table->string('title', 150)->default('')->comment('文章题目');
            $table->text('content')->comment('文章内容');
            $table->string('author', 30)->default('')->comment('文章作者');
            $table->string('author_email', 60)->default('')->comment('文章作者的email');
            $table->string('keywords')->default('')->comment('文章的关键字');
            $table->boolean('article_type')->default(2)->comment('文章类型，0，普通；1，置顶；2和大于2的，为保留文章，保留文章不能删除');
            $table->boolean('is_open')->default(1)->index('is_open')->comment('是否显示。1，显示；0，不显示');
            $table->integer('add_time')->unsigned()->default(0)->comment('文章添加时间');
            $table->string('file_url')->default('')->comment('上传文件或者外部文件的url');
            $table->boolean('open_type')->default(0)->index('open_type')->comment('0,正常；当该字段为1或者2时，会在文章最后添加一个链接“相关下载”，连接地址等于file_url的值');
            $table->string('link')->default('')->comment('该文章标题所引用的连接，如果该项有值将不能显示文章内容，即该表中content的值');
            $table->string('description')->default('')->comment('网页描述');
            $table->integer('sort_order')->unsigned()->default(50)->index()->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '文章内容'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('article');
    }
}
