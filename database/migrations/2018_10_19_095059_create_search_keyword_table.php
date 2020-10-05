<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSearchKeywordTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'search_keyword';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('keyword_id');
            $table->string('keyword', 500)->default('')->index('keyword')->comment('关键字');
            $table->string('pinyin', 1000)->default('')->comment('拼音');
            $table->boolean('is_on')->default(0)->comment('是否启用 0 否， 1 启用');
            $table->integer('count')->default(0)->comment('访问次数');
            $table->string('addtime', 20)->default('')->comment('添加时间');
            $table->string('pinyin_keyword', 2000)->default('')->comment('搜索拼音');
            $table->integer('result_count')->unsigned()->default(0)->comment('结果数量');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '搜索关键字'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('search_keyword');
    }
}
