<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangRegionToOfflineStoreTable extends Migration
{
    private $table = 'offline_store';

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

        if (Schema::hasColumn($this->table, 'country')) {

            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('country')->change();
            });
        }

        if (Schema::hasColumn($this->table, 'province')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('province')->change();
            });
        }

        if (Schema::hasColumn($this->table, 'city')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('city')->change();
            });
        }

        if (Schema::hasColumn($this->table, 'district')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('district')->change();
            });
        }

        if (Schema::hasColumn($this->table, 'street')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->integer('street')->change();
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
        Schema::table($this->table, function (Blueprint $table) {
            //
        });
    }
}
