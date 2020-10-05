<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSuppliersAddKfQqDataTable extends Migration
{
    protected $tableName = 'suppliers'; 

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'kf_qq')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('kf_qq')->default('')->comment('客服QQ');
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
        if (Schema::hasColumn($this->tableName, 'kf_qq')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('kf_qq');
            });
        }
    }
}
