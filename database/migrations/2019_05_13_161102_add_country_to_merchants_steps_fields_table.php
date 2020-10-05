<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountryToMerchantsStepsFieldsTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'merchants_steps_fields';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'country')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->unsignedInteger('country')->comment('国家/地区');
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
        if (Schema::hasColumn($this->table, 'country')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('country');
            });
        }
    }
}
