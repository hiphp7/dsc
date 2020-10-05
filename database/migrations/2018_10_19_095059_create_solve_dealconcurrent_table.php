<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSolveDealconcurrentTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'solve_dealconcurrent';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index()->comment('会员ID');
            $table->text('orec_id')->nullable()->comment('商品购物车ID');
            $table->integer('add_time')->unsigned()->default(0)->index('add_time')->comment('添加时间');
            $table->boolean('solve_type')->default(0)->index('solve_type')->comment('处理并发事件类型：0 前台');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '并发队列数据'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('solve_dealconcurrent');
    }
}
