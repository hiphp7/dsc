<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTransportTplTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_transport_tpl';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('tpl_name')->comment('模板名称');
            $table->integer('tid')->default(0)->index('tid')->comment('运费模板id，dsc_goods_transport表自增id');
            $table->integer('user_id')->default(0)->index('user_id')->comment('商家id');
            $table->integer('shipping_id')->default(0)->index('shipping_id')->comment('快递ID');
            $table->text('region_id')->comment('地区id');
            $table->text('configure')->comment('序列化的该配送区域的费用配置信息');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('管理员');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品运费模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_transport_tpl');
    }
}
