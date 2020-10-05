<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateKdniaoCustomerAccountTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'kdniao_customer_account';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->index()->comment('商家id');
            $table->integer('shipping_id')->unsigned()->default(0)->index()->comment('配送方式id');
            $table->string('shipper_code')->default('')->comment('配送方式code');
            $table->string('station_code')->default('')->comment('网点编码');
            $table->string('station_name')->default('')->comment('网点名称');
            $table->string('apply_id')->default('')->comment('申请ID');
            $table->string('company')->default('')->comment('公司');
            $table->string('name')->default('')->comment('名称');
            $table->string('tel')->default('')->comment('电话');
            $table->string('mobile')->default('')->comment('手机');
            $table->string('province_name')->default('')->comment('省份名称');
            $table->string('province_code')->default('')->comment('省份代码');
            $table->string('city_name')->default('')->comment('城市名称');
            $table->string('city_code')->default('')->comment('城市代码');
            $table->string('exp_area_name')->default('')->comment('地区名称');
            $table->string('exp_area_code')->default('')->comment('地区编码');
            $table->string('address')->default('')->comment('地址');
            $table->integer('province')->unsigned()->default(0)->comment('省份ID');
            $table->integer('city')->unsigned()->default(0)->comment('城市ID');
            $table->integer('district')->unsigned()->default(0)->comment('地区ID');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '快递鸟客户号信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('kdniao_customer_account');
    }
}
