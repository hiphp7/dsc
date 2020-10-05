<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserNameFilterWordsLogsTable extends Migration
{
    protected $table = 'filter_words_logs';

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
        if (!Schema::hasColumn($this->table, 'user_name')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('user_name')->default('')->comment('会员名称');
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
        if (Schema::hasTable($this->table)) {
            return false;
        }

        if (Schema::hasColumn($this->table, 'user_name')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('user_name');
            });
        }
    }
}
