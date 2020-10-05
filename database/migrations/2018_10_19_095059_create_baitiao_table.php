<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBaitiaoTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'baitiao';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('baitiao_id')->comment('自增ID号');
            $table->integer('user_id')->comment('用户id');
            $table->decimal('amount', 10)->default(0.00)->comment('白条金额');
            $table->string('repay_term', 50)->comment('还款期限');
            $table->integer('over_repay_trem')->default(0)->comment('超过还款期限的天数');
            $table->string('add_time')->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '白条'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('baitiao');
    }
}
