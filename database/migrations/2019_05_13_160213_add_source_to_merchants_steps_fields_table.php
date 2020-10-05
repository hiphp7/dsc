<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSourceToMerchantsStepsFieldsTable extends Migration
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
        if (!Schema::hasColumn($this->table, 'source')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('source')->comment('仓库来源');
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
        if (Schema::hasColumn($this->table, 'source')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
}
