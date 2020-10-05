<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerGradeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_grade';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('grade_name')->default('')->comment('等级名称');
            $table->integer('goods_sun')->default(0)->comment('发布商品数量');
            $table->integer('seller_temp')->default(0)->comment('模板数量');
            $table->string('favorable_rate', 20)->default('')->comment('优惠比例（字段暂时废弃）');
            $table->integer('give_integral')->unsigned()->default(0)->comment('赠送消费积分比例');
            $table->integer('rank_integral')->unsigned()->default(0)->comment('赠送等级积分比例');
            $table->integer('pay_integral')->unsigned()->default(0)->comment('积分购买金额比例');
            $table->boolean('white_bar')->default(0)->comment(' 白条');
            $table->string('grade_introduce')->default('')->comment('等级说明');
            $table->text('entry_criteria')->nullable()->comment('加入标准（序列化）');
            $table->string('grade_img')->default('')->comment('等级图片标志');
            $table->boolean('is_open')->default(0)->comment('是否打开，0为否，1为是（打开是商家可申请）');
            $table->boolean('is_default')->default(0)->comment('是否默认，0为否，1为是（是否商家入驻成功，默认为该等级，自动继承该等级权限）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家等级'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_grade');
    }
}
