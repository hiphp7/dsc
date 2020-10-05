<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateReturnCauseTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'return_cause';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('cause_id')->comment('自增id');
            $table->string('cause_name', 50)->default('')->comment('退换货原因');
            $table->integer('parent_id')->default(0)->comment('父级id');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_show')->default(0)->comment('是否显示');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '商品退换货订单原因'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('return_cause');
    }
}
