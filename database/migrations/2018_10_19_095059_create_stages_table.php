<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'stages';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('stages_id')->comment('分期表的ID');
            $table->string('order_sn', 20)->index('order_sn')->comment('订单编号');
            $table->boolean('stages_total')->default(0)->comment('总分期数');
            $table->decimal('stages_one_price', 10)->unsigned()->comment('每期的金额');
            $table->boolean('yes_num')->default(0)->comment('已还期数');
            $table->integer('create_date')->unsigned()->default(0)->comment('分期单创建时间');
            $table->text('repay_date')->nullable()->comment('还款日期');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品分期'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stages');
    }
}
