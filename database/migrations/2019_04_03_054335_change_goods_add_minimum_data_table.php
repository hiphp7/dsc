<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGoodsAddMinimumDataTable extends Migration
{
    protected $tableName = 'goods'; // 拼团开团记录表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'is_minimum')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('is_minimum')->unsigned()->default(0)->index('is_minimum')->comment('是否支持最小起订量');
            });
        }
		if (!Schema::hasColumn($this->tableName, 'minimum_start_date')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('minimum_start_date')->unsigned()->default(0)->comment('起订量开始时间');
            });
        }
		if (!Schema::hasColumn($this->tableName, 'minimum_end_date')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('minimum_end_date')->unsigned()->default(0)->comment('起订量结束时间');
            });
        }
		if (!Schema::hasColumn($this->tableName, 'minimum')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('minimum')->unsigned()->default(0)->comment('最小起订量');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'is_minimum')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('is_minimum');
            });
        }
		if (Schema::hasColumn($this->tableName, 'minimum_start_date')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('minimum_start_date');
            });
        }
		if (Schema::hasColumn($this->tableName, 'minimum_end_date')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('minimum_end_date');
            });
        }
		if (Schema::hasColumn($this->tableName, 'minimum')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('minimum');
            });
        }
    }
}
