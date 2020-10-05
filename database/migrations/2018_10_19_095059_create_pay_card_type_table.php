<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePayCardTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'pay_card_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('type_id')->comment('自增ID号');
            $table->string('type_name', 40)->comment('充值卡类型名称');
            $table->decimal('type_money', 10)->unsigned()->default(0.00)->comment('充值卡类型金额');
            $table->string('type_prefix', 10)->comment('充值卡生成卡号固定前缀');
            $table->string('use_end_date', 60)->comment('使用截止时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '充值卡类型'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pay_card_type');
    }
}
