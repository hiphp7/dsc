<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNamePinyinToBrandTable extends Migration
{
    protected $table = 'brand';

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

        if (!Schema::hasColumn($this->table, 'name_pinyin')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('name_pinyin', 255)->default('')->comment('品牌拼音名称');
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
        if (Schema::hasColumn($this->table, 'name_pinyin')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('name_pinyin');
            });
        }
    }
}
