<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserTypeToConnectUserTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'connect_user';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'user_type')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('user_type', 30)->default('user')->comment('用户类型（user用户，merchant商家，admin平台）');
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
        if (Schema::hasColumn($this->table, 'user_type')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
        }
    }
}
