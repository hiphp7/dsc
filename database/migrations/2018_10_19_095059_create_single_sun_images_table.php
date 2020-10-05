<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSingleSunImagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'single_sun_images';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('order_id')->unsigned()->default(0)->index('order_id');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id');
            $table->string('img_file');
            $table->string('img_thumb');
            $table->string('cont_desc', 2000);
            $table->integer('comment_id')->unsigned()->default(0)->index('single_id');
            $table->boolean('img_type')->default(0);
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '此表已废弃'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('single_sun_images');
    }
}
