<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWxappConfigAddWxcertTable extends Migration
{
    protected $tableName = 'wxapp_config'; // 小程序配置表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'wxcert')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('wxcert')->nullable()->comment('证书配置');
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
        if (Schema::hasColumn($this->tableName, 'wxcert')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('wxcert');
            });
        }
    }
}
