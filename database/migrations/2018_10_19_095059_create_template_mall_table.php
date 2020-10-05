<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTemplateMallTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'template_mall';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('temp_id')->comment('自增ID');
            $table->string('temp_file')->comment('备用字段，暂时无用');
            $table->boolean('temp_mode')->default(0)->comment('模板收费模式（0：免费，1：收费）');
            $table->decimal('temp_cost', 10)->default(0.00)->comment('模板费用');
            $table->string('temp_code', 60)->comment('模板名称');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->integer('sales_volume')->default(0)->comment('销量');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺购买可视化模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('template_mall');
    }
}
