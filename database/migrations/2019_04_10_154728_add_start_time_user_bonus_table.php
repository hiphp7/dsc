<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStartTimeUserBonusTable extends Migration
{
    protected $table;

    public function __construct()
    {
        $this->table = 'user_bonus';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'start_time')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('start_time')->unsigned()->default(0)->index('start_time')->comment('使用开始时间');
            });
        }

        if (!Schema::hasColumn($this->table, 'end_time')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('end_time')->unsigned()->default(0)->index('end_time')->comment('使用结束时间');
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
        if (Schema::hasColumn($this->table, 'start_time')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('start_time');
            });
        }
        
        if (Schema::hasColumn($this->table, 'end_time')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('end_time');
            });
        }
    }
}
