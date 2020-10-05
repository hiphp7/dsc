<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsGradeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_grade';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('ru_id')->unsigned()->default(0)->comment('商家ID');
            $table->integer('grade_id')->unsigned()->default(0)->comment(' 等级ID');
            $table->integer('add_time')->default(0)->comment('新增时间');
            $table->integer('year_num')->default(0)->comment('年限');
            $table->decimal('amount', 10, 2)->default(0.00)->comment('总金额');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家等级'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_grade');
    }
}
