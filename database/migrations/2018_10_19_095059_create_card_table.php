<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create('card', function (Blueprint $table) {
            $table->increments('card_id')->comment('自增ID');
            $table->string('card_name', 120)->default('')->index('card_name')->comment('贺卡名称');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('card_img')->default('')->comment('贺卡图纸的名称');
            $table->decimal('card_fee', 6)->unsigned()->default(0.00)->comment('贺卡所需费用');
            $table->decimal('free_money', 6)->unsigned()->default(0.00)->comment('订单达到该字段的值后使用此贺卡免费');
            $table->string('card_desc')->default('')->comment('贺卡的描述');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '贺卡的配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('card');
    }
}
