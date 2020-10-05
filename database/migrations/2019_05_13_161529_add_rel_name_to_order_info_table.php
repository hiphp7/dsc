<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRelNameToOrderInfoTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'order_info';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'rel_name')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('rel_name')->default('')->comment('真实姓名');
            });
        }

        if (!Schema::hasColumn($this->table, 'id_num')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('id_num')->default('')->comment('身份证号码');
            });
        }

        if (!Schema::hasColumn($this->table, 'rate_fee')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('rate_fee')->default('0.00')->comment('综合税费');
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
        if (Schema::hasColumn($this->table, 'rel_name')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('rel_name');
            });
        }
        if (Schema::hasColumn($this->table, 'id_num')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('id_num');
            });            
        }
        if (Schema::hasColumn($this->table, 'rate_fee')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('rate_fee');
            });                
        }
    }
}
