<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStagesRateStagesTable extends Migration
{
    private $table = 'stages';

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

        if (!Schema::hasColumn($this->table, 'stages_rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('stages_rate', 10, 2)->default('0.00')->comment('商品的费率');
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
        if (Schema::hasColumn($this->table, 'stages_rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('stages_rate');
            });
        }
    }
}
