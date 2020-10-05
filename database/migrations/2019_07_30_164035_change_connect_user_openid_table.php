<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeConnectUserOpenidTable extends Migration
{
    protected $tableName = 'connect_user';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        if (Schema::hasColumn($this->tableName, 'open_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropIndex('open_id');
                $table->index('open_id', 'open_id');
                $table->index('connect_code', 'connect_code');
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
        if (!Schema::hasTable($this->tableName)) {
            return false;
        }

        if (Schema::hasColumn($this->tableName, 'open_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropIndex('open_id');
                $table->dropIndex('connect_code');
                $table->index(['connect_code', 'open_id'], 'open_id');
            });
        }
    }
}
