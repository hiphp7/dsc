<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdPositionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'ad_position';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('position_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID');
            $table->string('position_name', 60)->default('')->comment('广告位名称');
            $table->integer('ad_width')->unsigned()->default(0)->comment('广告位宽度');
            $table->integer('ad_height')->unsigned()->default(0)->comment('广告位高度');
            $table->string('position_model')->default('')->comment('广告位结构');
            $table->string('position_desc')->default('')->comment('广告位描述');
            $table->text('position_style')->comment('广告位模板代码');
            $table->boolean('is_public')->default(0)->comment('是否公共（0-否，1-是：商家可用此广告位置）');
            $table->string('theme', 160)->default('')->index('theme')->comment('商城模板名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '广告位置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ad_position');
    }
}
