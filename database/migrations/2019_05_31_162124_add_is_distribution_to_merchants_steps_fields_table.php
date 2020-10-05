<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsDistributionToMerchantsStepsFieldsTable extends Migration
{
    /**
     * @var string
     */
    protected $tableName = 'merchants_steps_fields';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->tableName, 'is_distribution')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('is_distribution')->comment('是否开启分销');
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
        if (Schema::hasColumn($this->tableName, 'is_distribution')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('is_distribution');
            });
        }
    }
}
