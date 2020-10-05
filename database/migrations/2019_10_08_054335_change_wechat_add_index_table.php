<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatAddIndexTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 添加索引
        $tableName = 'wechat_user';
        if (Schema::hasColumn($tableName, 'openid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('openid');
                $table->index('ect_uid');
            });
        }

        $tableName = 'wechat_prize';
        if (Schema::hasColumn($tableName, 'openid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('openid');
            });
        }

        $tableName = 'wechat_point';
        if (Schema::hasColumn($tableName, 'openid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('openid');
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
        // 删除索引
        $tableName = 'wechat_user';
        if (Schema::hasColumn($tableName, 'openid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('openid');
                $table->dropIndex('ect_uid');
            });
        }

        $tableName = 'wechat_prize';
        if (Schema::hasColumn($tableName, 'openid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('openid');
            });
        }

        $tableName = 'wechat_point';
        if (Schema::hasColumn($tableName, 'openid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('openid');
            });
        }
    }
}
