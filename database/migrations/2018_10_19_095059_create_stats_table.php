<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStatsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'stats';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->integer('access_time')->unsigned()->default(0)->index('access_time')->comment('访问时间');
            $table->string('ip_address', 15)->default('')->comment('访问者ip');
            $table->integer('visit_times')->unsigned()->default(1)->comment('访问次数');
            $table->string('browser', 60)->default('')->comment('浏览器及版本');
            $table->string('system', 20)->default('')->comment('操作系统');
            $table->string('language', 20)->default('')->comment('语言');
            $table->string('area', 30)->default('')->comment('ip所在地区');
            $table->string('referer_domain', 100)->default('')->comment('页面访问来源域名');
            $table->string('referer_path', 200)->default('')->comment('页面访问来源除域名外的路径部分');
            $table->string('access_url')->default('')->comment('访问页面文件名');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '访问信息记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stats');
    }
}
