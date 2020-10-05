<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUsersRealCardImgDataTable extends Migration
{
    protected $tableName = 'users_real'; 

    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        if (Schema::hasColumn($this->tableName, 'front_of_id_card')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                //修改字段结构
                $table->string('front_of_id_card', 255)->default('')->change();
            });
        }
		 if (Schema::hasColumn($this->tableName, 'reverse_of_id_card')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                //修改字段结构
                $table->string('reverse_of_id_card', 255)->default('')->change();
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
        if (Schema::hasColumn($this->tableName, 'front_of_id_card')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('front_of_id_card',50)->default('')->change();
            });
        }
		if (Schema::hasColumn($this->tableName, 'reverse_of_id_card')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('reverse_of_id_card',50)->default('')->change();
            });
        }
    }
}
