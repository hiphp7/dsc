<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGiftGardTypeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'gift_gard_type';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('gift_id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('gift_name', 100)->comment('提货卡名称');
            $table->decimal('gift_menory', 10)->nullable()->comment('礼品卡金额');
            $table->decimal('gift_min_menory', 10)->nullable()->comment('最小订单金额');
            $table->integer('gift_start_date')->comment('使用起始日期');
            $table->integer('gift_end_date')->comment('使用结束日期');
            $table->smallInteger('gift_number')->comment('发放数量');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content')->comment('审核回复内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '提货卡'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gift_gard_type');
    }
}
