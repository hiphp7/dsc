<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAreaRegionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'area_region';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->integer('shipping_area_id')->unsigned()->default(0)->primary()->comment('配送区域的id号，等同于shipping_area的shipping_area_id的值');
            $table->integer('region_id')->unsigned()->default(0)->index('region_id')->comment('配送地区ID，同region的region_id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同users表user_id）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '配送区域'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('area_region');
    }
}
