<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCollectBrandTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'collect_brand';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->integer('brand_id')->unsigned()->default(0)->index('brand_id')->comment('品牌ID');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('ru_id')->default(0)->index('ru_id')->comment('商家ID');
            $table->integer('user_brand')->unsigned()->default(0)->index('user_brand')->comment('已废弃');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '品牌关注'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('collect_brand');
    }
}
