<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFreeRateToGoodsTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'goods'; 

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'free_rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->tinyInteger('free_rate')->comment('是否免税 0 否 1 是');
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
        if (Schema::hasColumn($this->table, 'free_rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('free_rate');
            });                
        }
    }
}
