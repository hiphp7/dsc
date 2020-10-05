<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpShopAddApplyTimeTable extends Migration
{
    protected $tableName = 'drp_shop'; // 分销商表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'apply_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('apply_time')->unsigned()->default(0)->comment('权益申请时间');
            });
        }

        if (!Schema::hasColumn($this->tableName, 'expiry_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('expiry_type')->default('')->comment('权益卡有效期类型：forever(永久), days(多少天数), timespan(时间间隔)');
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
        if (Schema::hasColumn($this->tableName, 'apply_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('apply_time');
            });
        }
        if (Schema::hasColumn($this->tableName, 'expiry_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('expiry_type');
            });
        }
    }
}
