<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAreaLinkGoodsTable extends Migration
{
    protected $table = 'goods';

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
        if (!Schema::hasColumn($this->table, 'area_link')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('area_link')->default(0)->index('area_link')->comment('判断是否关联地区');
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
        if (Schema::hasTable($this->table)) {
            return false;
        }

        if (Schema::hasColumn($this->table, 'area_link')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('area_link');
            });
        }
    }
}
