<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpV143AddFiledsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = 'drp_log';
        if (!Schema::hasColumn($tableName, 'level_percent')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('level_percent', 10, 2)->default(0)->comment('层级佣金比例');
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
        $tableName = 'drp_log';
        // 删除字段
        if (Schema::hasColumn($tableName, 'level_percent')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('level_percent');
            });
        }
    }
}
