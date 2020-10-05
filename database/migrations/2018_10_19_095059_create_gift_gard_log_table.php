<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGiftGardLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'gift_gard_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('admin_id')->unsigned()->default(0)->index('admin_id')->comment('操作管理员');
            $table->integer('gift_gard_id')->unsigned()->default(0)->index('gift_gard_id')->comment('提货卡ID，同dsc_gift_gard_type的gift_id ');
            $table->string('delivery_status', 60)->comment('提货状态（0-未提货，1-已提货）');
            $table->integer('addtime')->default(0)->comment('添加时间');
            $table->string('handle_type', 20)->comment('日志类型');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '提货卡日志'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gift_gard_log');
    }
}
