<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAutoManageTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'auto_manage';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->integer('item_id')->primary()->comment('如果是商品就是goods的goods_id，如果是文章就是article的article_id');
            $table->string('type')->default('')->comment('goods是商品，article是文章');
            $table->integer('starttime')->default(0)->comment('上线时间');
            $table->integer('endtime')->default(0)->comment('下线时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品自动上下线的计划任务'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('auto_manage');
    }
}
