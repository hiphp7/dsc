<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateTypeBonusTypeTable extends Migration
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
        if (!Schema::hasColumn($this->table, 'date_type')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('date_type')->default(0)->index('date_type')->comment('时间类型');
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
        if (Schema::hasColumn($this->table, 'date_type')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('date_type');
            });
        }
    }
}
