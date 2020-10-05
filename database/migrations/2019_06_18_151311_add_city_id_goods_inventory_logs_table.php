<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCityIdGoodsInventoryLogsTable extends Migration
{
    protected $table = 'goods_inventory_logs';

    /**
     * Run the migrations.
     *
     * @return bool
     */
    public function up()
    {
        if (!Schema::hasTable($this->table)) {
            return false;
        }

        if (!Schema::hasColumn($this->table, 'city_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('city_id')->index('city_id')->default(0)->comment('仓库城市-区县');
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
