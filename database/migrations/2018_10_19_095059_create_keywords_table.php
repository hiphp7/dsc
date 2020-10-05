<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateKeywordsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'keywords';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->date('date')->default('1000-01-01')->comment('日期时间');
            $table->string('searchengine', 20)->default('')->comment('搜索来源');
            $table->string('keyword', 90)->default('')->comment('关键字');
            $table->integer('count')->unsigned()->default(0)->comment('搜索次数');
            $table->primary(['date','searchengine','keyword']);
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
        Schema::drop('keywords');
    }
}
