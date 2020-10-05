<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVolumePriceTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'volume_price';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->boolean('price_type')->index('price_type')->comment('价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)');
            $table->integer('goods_id')->unsigned()->index('goods_id')->comment('商品ID');
            $table->integer('volume_number')->unsigned()->default(0)->index('volume_number')->comment('商品优惠数量');
            $table->decimal('volume_price', 10)->default(0.00)->index('volume_price')->comment('商品优惠价格');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品数量阶梯价格'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('volume_price');
    }
}
