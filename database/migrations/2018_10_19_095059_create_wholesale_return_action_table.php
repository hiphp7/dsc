<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleReturnActionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_return_action';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('action_id')->comment('自增ID');
            $table->integer('ret_id')->unsigned()->default(0)->index('order_id')->comment('退换货订单ID');
            $table->string('action_user', 30)->default('')->comment('操作该次的人员');
            $table->boolean('return_status')->default(0)->comment('-1：仅退款，0：申请，1：收到退换货，2：换出商品寄出 分单，3：换出商品寄出，4：完成退换货，5：同意申请，6：拒绝申请');
            $table->boolean('refound_status')->default(0)->comment('0：未退款，1：已退款，2：已换货，3：已维修，4：未换货，5：未维修');
            $table->boolean('action_place')->default(0)->comment('取消订单记录，值为1');
            $table->string('action_note')->default('')->comment('操作备注');
            $table->integer('log_time')->unsigned()->default(0)->comment('操作时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '供应链退换货操作记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_return_action');
    }

}
