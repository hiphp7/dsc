<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeMerchantsShopInformationAddUpdatetimeTable extends Migration
{
    protected $tableName = 'merchants_shop_information';

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

        if (!Schema::hasColumn($this->tableName, 'update_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->integer('update_time')->unsigned()->default(0)->comment('更新时间');
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

        if (Schema::hasColumn($this->tableName, 'update_time')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('update_time');
            });
        }
    }
}
