<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseFreightTplTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'warehouse_freight_tpl';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('tpl_name')->default('')->comment('运费模板名称');
            $table->integer('user_id')->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('warehouse_id')->default(0)->index('warehouse_id')->comment('仓库ID');
            $table->integer('shipping_id')->default(0)->index('shipping_id')->comment('配送方式ID');
            $table->integer('region_id')->default(0)->index('region_id')->comment('仓库地区ID');
            $table->text('configure')->nullable()->comment('运费基本配置信息');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '仓库运费模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('warehouse_freight_tpl');
    }
}
