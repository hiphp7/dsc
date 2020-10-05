<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    private $tableName = 'users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->tableName, 'drp_bind_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('drp_bind_time')->unsigned()->default(0)->comment('分销父级绑定时间');
                $table->integer('drp_bind_update_time')->unsigned()->default(0)->comment('绑定更新时间');
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
        if (Schema::hasColumn($this->tableName, 'drp_bind_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('drp_bind_time');
                $table->dropColumn('drp_bind_update_time');
            });
        }

    }
}
