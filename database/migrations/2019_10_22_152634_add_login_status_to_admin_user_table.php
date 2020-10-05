<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLoginStatusToAdminUserTable extends Migration
{
    protected $table = 'admin_user';

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

        if (!Schema::hasColumn($this->table, 'login_status')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->text('login_status')->comment('登录状态');
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
        if (Schema::hasColumn($this->table, 'login_status')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('login_status');
            });
        }
    }
}
