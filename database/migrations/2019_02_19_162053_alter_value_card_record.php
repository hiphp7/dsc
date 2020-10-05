<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterValueCardRecord extends Migration
{
    protected $tableName = 'value_card_record';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn($this->tableName, 'add_val')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                //修改字段结构
                $table->decimal('add_val', 10, 2)->default('0.00')->change();
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
        // 还原字段类型
        if (Schema::hasColumn($this->tableName, 'add_val')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('add_val')->unsigned()->default(0)->change();
            });
        }
    }
}
