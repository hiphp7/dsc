<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCouponsRegionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'coupons_region';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cf_id')->comment('自增ID');
            $table->integer('cou_id')->unsigned()->default(0)->index('cou_id')->comment('优惠券id');
            $table->text('region_list')->comment('地区（逗号相隔的字符串）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '优惠券使用地区'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('coupons_region');
    }
}
