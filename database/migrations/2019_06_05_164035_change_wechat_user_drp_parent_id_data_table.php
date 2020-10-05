<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatUserDrpParentIdDataTable extends Migration
{
    protected $tableName = 'wechat_user';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        if (Schema::hasColumn($this->tableName, 'drp_parent_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                //修改字段结构
                $table->integer('drp_parent_id')->default(0)->change();
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
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        // 还原字段类型
        if (Schema::hasColumn($this->tableName, 'drp_parent_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->boolean('drp_parent_id')->default(0)->change();
            });
        }
    }
}
