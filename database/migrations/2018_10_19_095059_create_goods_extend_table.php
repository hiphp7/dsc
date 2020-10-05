<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsExtendTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_extend';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('extend_id')->comment('自增ID');
            $table->integer('goods_id')->index('goods_id')->comment('商品id');
            $table->boolean('is_reality')->default(0)->index('is_reality')->comment('是否是正品0否1是');
            $table->boolean('is_return')->default(0)->index('is_return')->comment('是否支持包退服务0否1是');
            $table->boolean('is_fast')->default(0)->index('is_fast')->comment('是否闪速送货0否1是');
            $table->string('width', 50)->comment('宽度');
            $table->string('height', 50)->comment('高度');
            $table->string('depth', 50)->comment('深度');
            $table->string('origincountry', 50)->comment('原始国家');
            $table->string('originplace', 50)->comment('原始位置');
            $table->string('assemblycountry', 50)->comment('组装国家');
            $table->string('barcodetype', 50)->comment('条码类型');
            $table->string('catena', 50)->comment('产品系列');
            $table->string('isbasicunit', 50)->comment('基本单位');
            $table->string('packagetype', 50)->comment('打包类型');
            $table->string('grossweight', 50)->comment('总重量');
            $table->string('netweight', 50)->comment('净重');
            $table->string('netcontent', 50)->comment('净含量');
            $table->string('licensenum', 50)->comment('许可证号');
            $table->string('healthpermitnum', 50)->comment('健康证号码');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品扩展信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_extend');
    }
}
