<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDrpShopTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_shop';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->unique()->comment('会员id');
            $table->string('shop_name')->default('')->comment('店铺名称');
            $table->string('real_name')->default('')->comment('真实姓名');
            $table->string('mobile')->default('')->comment('手机号');
            $table->string('qq')->default('')->comment('qq');
            $table->string('shop_img')->default('')->comment('店铺背景图');
            $table->string('shop_portrait')->default('')->comment('店铺头像');
            $table->integer('cat_id')->unsigned()->default(0)->comment('分类id');
            $table->integer('create_time')->unsigned()->default(0)->comment('创建时间');
            $table->boolean('isbuy')->default(0)->comment('是否购买成为分销商');
            $table->boolean('audit')->default(0)->comment('店铺审核,0未审核,1已审核');
            $table->boolean('status')->default(0)->comment('店铺状态');
            $table->decimal('shop_money', 10, 2)->default(0.00)->comment('获得佣金');
            $table->integer('shop_points')->unsigned()->default(0)->comment('获得积分');
            $table->boolean('type')->default(2)->comment('分销商品类型：0全部，1分类，2商品');
            $table->integer('credit_id')->unsigned()->default(0)->comment('分销商等级id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销商店铺'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('drp_shop');
    }
}
