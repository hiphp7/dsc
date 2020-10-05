<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsChangeLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_change_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('log_id')->comment('自增日志ID');
            $table->integer('goods_id')->index('goods_id')->comment('商品ID');
            $table->decimal('shop_price', 10, 2)->comment('本店价');
            $table->decimal('shipping_fee', 10, 2)->comment('运费');
            $table->decimal('promote_price', 10, 2)->comment('促销价');
            $table->string('member_price')->comment('会员价');
            $table->string('volume_price')->comment('阶梯价');
            $table->integer('give_integral')->comment('赠送消费积分');
            $table->integer('rank_integral')->comment('赠送等级积分');
            $table->decimal('goods_weight', 10, 3)->comment('商品重量');
            $table->boolean('is_on_sale')->comment('上下架');
            $table->integer('user_id')->comment('操作者ID');
            $table->integer('handle_time')->comment('操作时间');
            $table->boolean('old_record')->default(0)->comment('原纪录');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品操作日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_change_log');
    }
}
