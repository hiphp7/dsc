<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRegionWarehouseTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'region_warehouse';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('region_id')->comment('自增ID号');
            $table->integer('regionId')->unsigned()->index('regionId')->comment('同dsc_region的region_id号');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('该地区的上一个节点的地区id');
            $table->string('region_name', 120)->default('')->comment('地区名称');
            $table->string('region_code')->default('')->index('region_code')->comment('地区编码');
            $table->boolean('region_type')->default(2)->index('region_type')->comment('地区级别');
            $table->integer('agency_id')->unsigned()->default(0)->index('agency_id')->comment('办事处的id,这里有一个bug,同一个省不能有多个办事处,该字段只记录最新的那个办事处的id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '仓库地区'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('region_warehouse');
    }
}
