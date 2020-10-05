<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMemberPriceAddPercentageTable extends Migration
{
    protected $tableName = 'member_price';

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

        if (!Schema::hasColumn($this->tableName, 'percentage')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->tinyInteger('percentage')->unsigned()->default(0)->comment('是否使用百分比：0否，1是; 配合member_price表 user_price');
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

        // 删除字段
        if (Schema::hasColumn($this->tableName, 'percentage')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('percentage');
            });
        }
    }
}
