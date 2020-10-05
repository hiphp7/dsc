<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNegativeIdToOrderReturnTable extends Migration
{
    private $table = 'order_return';

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

        if (!Schema::hasColumn($this->table, 'negative_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('negative_id')->index('negative_id')->default(0)->comment('负账单ID');
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
        if (Schema::hasColumn($this->table, 'negative_id')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('negative_id');
            });
        }
    }
}
