<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateShippingPointTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'shipping_point';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('shipping_area_id')->unsigned()->default(0)->index('shipping_area_id')->comment('同dsc_shipping_date的shipping_area_id');
            $table->string('name', 30)->default('')->comment('自提点名称');
            $table->string('user_name', 30)->default('')->comment('使用名称');
            $table->string('mobile', 13)->default('')->comment('联系方式');
            $table->string('address')->default('')->comment('地址');
            $table->string('img_url')->default('')->comment('图片文件');
            $table->string('anchor', 30)->default('')->comment('拼音名称');
            $table->string('line')->default('')->comment('到达路线');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '自提点配送地址信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping_point');
    }
}
