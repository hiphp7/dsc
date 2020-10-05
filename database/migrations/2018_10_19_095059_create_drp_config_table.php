<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDrpConfigTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'drp_config';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('code')->default('')->comment('关键词');
            $table->string('type')->default('')->comment('字段类型');
            $table->string('store_range')->default('')->comment('值范围');
            $table->text('value')->comment('值');
            $table->string('name')->default('')->comment('字段中文名称');
            $table->string('warning')->default('')->comment('提示');
            $table->integer('sort_order')->unsigned()->default(0)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '分销基本配置信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('drp_config');
    }
}
