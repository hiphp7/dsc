<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRegionidRegionWarehouseTable extends Migration
{
    protected $table = 'region_warehouse';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table)) {
            return false;
        }

        if (Schema::hasColumn($this->table, 'regionid')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->renameColumn('regionid', 'regionId');
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
        Schema::table($this->table, function (Blueprint $table) {
            //
        });
    }
}
