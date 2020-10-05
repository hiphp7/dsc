<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsRegionAreaTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_region_area';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('ra_id')->comment('自增ID');
            $table->string('ra_name')->default('')->comment('区域名称');
            $table->integer('ra_sort')->unsigned()->default(0)->comment('排序');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('up_titme')->unsigned()->default(0)->comment('更新时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '区域管理'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_region_area');
    }
}
