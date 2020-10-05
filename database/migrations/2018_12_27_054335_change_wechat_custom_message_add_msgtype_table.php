<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatCustomMessageAddMsgtypeTable extends Migration
{
    protected $tableName = 'wechat_custom_message'; // 微信通消息日志表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'msgtype')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('msgtype')->default('')->comment('消息类型');
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
        if (Schema::hasColumn($this->tableName, 'msgtype')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('msgtype');
            });
        }
    }
}
