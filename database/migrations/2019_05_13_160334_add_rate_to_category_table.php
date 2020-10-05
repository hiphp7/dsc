<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRateToCategoryTable extends Migration
{
    /**
     * @var string
     */
    private $table = 'category';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->table, 'rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->decimal('rate', 10, 2)->comment('海关税率');
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
        if (Schema::hasColumn($this->table, 'rate')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('rate');
            });
        }
    }
}
