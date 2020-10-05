<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateExchangeGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'exchange_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('eid')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->unique('goods_id')->comment('商品ID，同dsc_goods的goods_id');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content', 1000)->comment('审核回复内容');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('会员ID');
            $table->integer('exchange_integral')->unsigned()->default(0)->comment('积分值');
            $table->integer('market_integral')->unsigned()->default(0)->comment('市场积分');
            $table->boolean('is_exchange')->default(0)->index('is_exchange')->comment('是否可兑换（0-可兑换，1-不可兑换）');
            $table->boolean('is_hot')->default(0)->index('is_hot')->comment('是否热销(0-非热销，1热销)');
            $table->boolean('is_best')->default(0)->index('is_best')->comment('是否精品(0-非精品，1精品)');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '积分商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('exchange_goods');
    }
}
