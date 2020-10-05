<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBonusTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'bonus_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('type_id')->comment('自增ID');
            $table->string('type_name')->default('')->comment('红包名称');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('商家ID（同users表user_id）');
            $table->decimal('type_money', 10, 2)->default(0.00)->comment('红包所值的金额');
            $table->boolean('send_type')->default(0)->comment('红包发送类型.0,按用户发放;1,按商品发放;2,按订单金额发放;3,线下发放的红包');
            $table->boolean('usebonus_type')->default(0)->comment('红包使用类型 (0-自主使用， 1-全场通用)');
            $table->decimal('min_amount', 10, 2)->unsigned()->default(0.00)->comment('如果是按金额发送红包,该项是最小金额.即只要购买超过该金额的商品都可以领到红包');
            $table->decimal('max_amount', 10, 2)->unsigned()->default(0.00)->comment('只要订单金额达到该数值，就会发放红包给用户（按订单金额发放）');
            $table->integer('send_start_date')->default(0)->comment('红包发送的开始时间');
            $table->integer('send_end_date')->default(0)->comment('红包发送的结束时间');
            $table->integer('use_start_date')->default(0)->comment('红包可以使用的开始时间');
            $table->integer('use_end_date')->default(0)->comment('红包可以使用的结束时间');
            $table->decimal('min_goods_amount', 10, 2)->unsigned()->default(0.00)->comment('可以使用该红包的商品的最低价格.即只要达到该价格的商品才可以使用红包');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->text('review_content')->comment('审核回复内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '红包类型'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bonus_type');
    }
}
