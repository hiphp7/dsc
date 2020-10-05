<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShippingAreaTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'shipping_area';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('shipping_area_id')->comment('自增ID');
            $table->string('shipping_area_name', 150)->default('')->comment('配送区域名称');
            $table->integer('shipping_id')->unsigned()->default(0)->index('shipping_id')->comment('配送方式ID');
            $table->text('configure')->nullable()->comment('序列化的该配送区域的费用配置信息');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '配送方式配送区域'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping_area');
    }
}
