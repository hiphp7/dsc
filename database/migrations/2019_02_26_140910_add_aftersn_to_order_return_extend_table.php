<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAftersnToOrderReturnExtendTable extends Migration
{
    protected $tableName = 'order_return_extend';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->tableName, 'aftersn')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('aftersn', 60)->default('');
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
        if (Schema::hasColumn($this->tableName, 'aftersn')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->drop('aftersn');
            });
        }
    }
}
