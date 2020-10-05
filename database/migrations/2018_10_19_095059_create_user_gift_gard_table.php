<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserGiftGardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_gift_gard';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('gift_gard_id')->comment('自增ID');
            $table->bigInteger('gift_sn')->unsigned()->index('gift_sn')->comment('提货卡号');
            $table->char('gift_password', 32)->comment('提货卡密');
            $table->integer('user_id')->unsigned()->nullable()->default(0)->index('user_id')->comment('会员ID');
            $table->integer('goods_id')->unsigned()->nullable()->default(0)->index('goods_id')->comment('商品ID');
            $table->integer('user_time')->unsigned()->nullable()->default(0)->comment('使用时间');
            $table->string('express_no', 64)->nullable()->default('')->comment('快递单号');
            $table->integer('gift_id')->unsigned()->index('gift_id')->comment('提货卡ID');
            $table->string('address', 120)->nullable()->comment('收货地址');
            $table->string('consignee_name', 60)->nullable()->comment('收货人名称');
            $table->string('mobile', 60)->nullable()->comment('联系方式');
            $table->boolean('status')->nullable()->default(0)->comment('发货状态');
            $table->string('config_goods_id')->nullable()->comment('可取商品');
            $table->boolean('is_delete')->nullable()->default(1)->index('is_delete')->comment('是否删除');
            $table->string('shipping_time', 20)->nullable()->comment('配送名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员提货卡信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_gift_gard');
    }
}
