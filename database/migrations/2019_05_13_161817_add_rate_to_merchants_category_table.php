<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRateToMerchantsCategoryTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'merchants_category';    

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('rate')->default('0.00')->comment('废弃字段');
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
        if (Schema::hasColumn($this->table, 'rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('rate');
            });                
        }
    }
}
