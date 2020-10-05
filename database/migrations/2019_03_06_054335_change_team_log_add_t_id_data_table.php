<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTeamLogAddTIdDataTable extends Migration
{
    protected $tableName = 'team_log'; // 拼团开团记录表

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 't_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('t_id')->unsigned()->default(0)->index('t_id')->comment('拼团活动id');
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
        if (Schema::hasColumn($this->tableName, 't_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('t_id');
            });
        }
    }
}
