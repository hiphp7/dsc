<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMemberPriceTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'member_price';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('price_id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品ID');
            $table->boolean('user_rank')->default(0)->index('user_rank')->comment('会员等级');
            $table->decimal('user_price', 10, 2)->default(0.00)->comment('会员价格');
            $table->index(['goods_id', 'user_rank'], 'goods_id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员等级价格'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('member_price');
    }
}
