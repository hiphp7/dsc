<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRegFieldsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'reg_fields';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('reg_field_name', 60)->comment('会员注册项名称');
            $table->boolean('dis_order')->default(100)->comment('排序');
            $table->boolean('display')->default(1)->comment('是否显示 （0否，1是）');
            $table->boolean('type')->default(0)->comment('是否必填 （0否，1是）');
            $table->boolean('is_need')->default(1)->comment('Display和type同时选择是');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员注册项设置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('reg_fields');
    }
}
