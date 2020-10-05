<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBookingGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'booking_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('rec_id')->comment('自增ID号');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('登记该缺货记录的用户的id，同users的user_id');
            $table->string('email', 60)->default('')->comment('页面填的用户的email，默认取值于users的email');
            $table->string('link_man', 60)->default('')->comment('页面填的用户的姓名，默认取值于users的consignee');
            $table->string('tel', 60)->default('')->comment('页面填的用户的电话，默认取值于users的tel');
            $table->integer('goods_id')->unsigned()->default(0)->index('goods_id')->comment('缺货登记的商品id，同goods的 goods_id');
            $table->string('goods_desc')->default('')->comment('缺货登记时留的订购描述');
            $table->integer('goods_number')->unsigned()->default(0)->comment('订购数量');
            $table->integer('booking_time')->unsigned()->default(0)->comment('缺货登记的时间');
            $table->boolean('is_dispose')->default(0)->comment('是否已经被处理');
            $table->string('dispose_user', 30)->default('')->comment('处理该缺货登记的管理员用户名，取值于session,该session取值于admin_user的user_name');
            $table->integer('dispose_time')->unsigned()->default(0)->comment('处理的时间');
            $table->string('dispose_note')->default('')->comment('处理时管理员留的备注');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '缺货登记的订购和处理记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('booking_goods');
    }
}
