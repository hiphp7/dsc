<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBargainStatisticsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'bargain_statistics';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增id');
            $table->integer('bs_id')->unsigned()->default(0)->comment('创建活动id');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->decimal('subtract_price', 10)->default(0.00)->comment('砍掉商品价格');
            $table->integer('add_time')->unsigned()->default(0)->comment('参与砍价时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '会员参与砍价活动'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bargain_statistics');
    }
}
