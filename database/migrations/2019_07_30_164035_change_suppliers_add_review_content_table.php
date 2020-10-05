<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSuppliersAddReviewContentTable extends Migration
{
    protected $tableName = 'suppliers';

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

        if (!Schema::hasColumn($this->tableName, 'review_content')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('review_content', 100)->default('')->comment('审核内容');
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

        if (Schema::hasColumn($this->tableName, 'review_content')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('review_content');
            });
        }
    }
}
