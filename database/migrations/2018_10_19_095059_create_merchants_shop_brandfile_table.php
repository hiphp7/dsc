<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsShopBrandfileTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_shop_brandfile';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('b_fid')->comment('自增ID');
            $table->integer('bid')->unsigned()->default(0)->index('bid')->comment('同dsc_merchants_shop_brand的bid');
            $table->string('qualificationNameInput')->default('')->comment('资质名称');
            $table->string('qualificationImg')->default('')->comment('资质电子版文件');
            $table->string('expiredDateInput')->default('')->comment('资质期日');
            $table->boolean('expiredDate_permanent')->default(0)->comment('是否永久（0-否，1-是）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品品牌资质'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_shop_brandfile');
    }
}
