<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleVolumePriceTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_volume_price';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->boolean('price_type')->index('price_type')->comment('价格类型');
            $table->integer('goods_id')->unsigned()->index('goods_id')->comment('商品id');
            $table->integer('volume_number')->unsigned()->default(0)->index('volume_number')->comment('优惠数量');
            $table->decimal('volume_price', 10, 2)->default(0.00)->index('volume_price')->comment('优惠价格');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '批发商品阶梯价格'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_volume_price');
    }
}
