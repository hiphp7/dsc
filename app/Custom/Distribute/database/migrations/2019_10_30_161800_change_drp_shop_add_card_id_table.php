<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpShopAddCardIdTable extends Migration
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
        if (!Schema::hasColumn($this->tableName, 'membership_card_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('会员权益id,关联 user_membership_card');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'open_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('open_time')->unsigned()->default(0)->comment('开店时间，权益开始时间');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'expiry_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('expiry_time')->unsigned()->default(0)->comment('权益过期时间');
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
        if (Schema::hasColumn($this->tableName, 'membership_card_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('membership_card_id');
            });
        }
        if (Schema::hasColumn($this->tableName, 'open_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('open_time');
            });
        }
        if (Schema::hasColumn($this->tableName, 'expiry_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('expiry_time');
            });
        }
    }
}
