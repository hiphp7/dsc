<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSearchengineTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'searchengine';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->date('date')->default('1000-01-01')->comment('搜索引擎访问日期');
            $table->string('searchengine', 20)->default('')->comment('搜索引擎名称');
            $table->integer('count')->unsigned()->default(0)->comment('访问次数');
            $table->primary(['date','searchengine']);
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '搜索引擎访问记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('searchengine');
    }
}
