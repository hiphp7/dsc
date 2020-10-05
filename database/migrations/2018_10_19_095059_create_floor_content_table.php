<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFloorContentTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'floor_content';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('fb_id')->comment('自增ID');
            $table->string('filename', 50)->comment('关联模板表filename');
            $table->string('region', 100)->comment('关联模板表region');
            $table->integer('id')->index('id')->comment('关联模板表id');
            $table->string('id_name', 100)->comment('id对应的内容名称');
            $table->integer('brand_id')->index('brand_id')->comment('品牌id');
            $table->string('brand_name', 100)->comment('品牌名称');
            $table->string('theme', 100)->index('theme')->comment('当前选择的模板');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '模板楼层设置品牌信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('floor_content');
    }
}
