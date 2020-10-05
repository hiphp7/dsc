<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlowTypeSolveDealconcurrentTable extends Migration
{
    protected $table = 'solve_dealconcurrent';

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
        if (!Schema::hasColumn($this->table, 'flow_type')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('flow_type')->default(0)->comment('商品类型（flow_type：秒杀、普通商品）');
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
        if (Schema::hasColumn($this->table, 'flow_type')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('flow_type');
            });
        }
    }
}
