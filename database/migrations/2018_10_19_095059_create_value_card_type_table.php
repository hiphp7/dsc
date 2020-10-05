<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateValueCardTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'value_card_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 180)->nullable()->comment('类型名称');
            $table->string('vc_desc')->nullable()->comment('描述');
            $table->decimal('vc_value', 10, 2)->default(0.00)->comment('面值');
            $table->string('vc_prefix', 10)->default('')->comment('前缀');
            $table->decimal('vc_dis', 10, 2)->default(1.00)->comment('折扣率');
            $table->boolean('vc_limit')->default(1)->comment('限制数量');
            $table->boolean('use_condition')->default(0)->index('use_condition')->comment('使用条件');
            $table->string('use_merchants')->default('self')->comment('可使用店铺');
            $table->string('spec_goods')->default('')->comment('指定商品');
            $table->string('spec_cat')->default('')->comment('指定分类');
            $table->boolean('vc_indate')->index('vc_indate')->comment('有效期单位为自然月');
            $table->boolean('is_rec')->default(0)->index('is_rec')->comment('可否充值');
            $table->integer('add_time')->default(0)->comment('新增时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '虚拟卡类型'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('value_card_type');
    }
}
