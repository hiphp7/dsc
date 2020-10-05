<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddValidPeriodBonusTypeTable extends Migration
{
    protected $table;

    public function __construct()
    {
        $this->table = 'bonus_type';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'valid_period')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('valid_period')->unsigned()->default(0)->index('valid_period')->comment('红包有效期');
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
        if (Schema::hasColumn($this->table, 'valid_period')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('valid_period');
            });
        }
    }
}
