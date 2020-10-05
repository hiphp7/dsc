<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTouchCatadsUrlToCategoryTable extends Migration
{
    private $table = 'category';

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

        if (!Schema::hasColumn($this->table, 'touch_catads_url')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('touch_catads_url', 255)->default('')->comment('手机分类列表广告链接');
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
        if (Schema::hasColumn($this->table, 'touch_catads_url')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('touch_catads_url');
            });
        }
    }
}
