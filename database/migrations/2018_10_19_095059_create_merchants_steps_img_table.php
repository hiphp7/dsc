<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsStepsImgTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_steps_img';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('gid')->comment('自增ID');
            $table->integer('tid')->unsigned()->default(0)->index('tid')->comment('关联merchants_steps_title表tid');
            $table->string('steps_img')->default('')->comment('图片');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '入驻流程图片（暂时废弃）'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_steps_img');
    }
}
