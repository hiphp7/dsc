<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCityIdWarehouseAreaAttrTable extends Migration
{
    private $table = 'warehouse_area_attr';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->table)) {
            return false;
        }

        if (!Schema::hasColumn($this->table, 'city_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('city_id')->index('city_id')->default(0)->comment('商品仓库地区-区县ID');
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
        if (Schema::hasColumn($this->table, 'city_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('city_id');
            });
        }
    }
}
