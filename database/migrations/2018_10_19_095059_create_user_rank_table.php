<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserRankTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_rank';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rank_id')->comment('自增ID');
            $table->string('rank_name', 30)->default('')->comment('会员等级名称');
            $table->integer('min_points')->unsigned()->default(0)->comment('该等级的最低积分');
            $table->integer('max_points')->unsigned()->default(0)->comment('该等级的最高积分');
            $table->boolean('discount')->default(0)->comment('该会员等级的商品折扣');
            $table->boolean('show_price')->default(1)->comment('是否在不是该等级会员购买页面显示该会员等级的折扣价格.1,显示;0,不显示');
            $table->boolean('special_rank')->default(0)->comment('是否事特殊会员等级组.0,不是;1,是');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员等级'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_rank');
    }
}
