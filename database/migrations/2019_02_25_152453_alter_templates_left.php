<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTemplatesLeft extends Migration
{
    protected $tableName = 'templates_left';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn($this->tableName, 'img_file')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                //修改字段结构
                $table->string('img_file', 255)->default('')->change();
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
        if (Schema::hasColumn($this->tableName, 'img_file')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('img_file',120)->default('')->change();
            });
        }
    }
}
