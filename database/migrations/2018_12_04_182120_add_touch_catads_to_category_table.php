<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTouchCatadsToCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('category', 'touch_catads')) {
            Schema::table('category', function (Blueprint $table) {
                $table->string('touch_catads')->comment('分类页广告');
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
        if (Schema::hasColumn('category', 'touch_catads')) {
            Schema::table('category', function (Blueprint $table) {
                $table->dropColumn('touch_catads');
            });
        }
    }
}
