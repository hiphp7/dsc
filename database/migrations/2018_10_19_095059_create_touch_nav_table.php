<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTouchNavTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'touch_nav';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('ctype', 10)->default('0')->comment('c--(直接选择的系统分类)');
            $table->integer('cid')->unsigned()->default(0)->comment('系统内容直接选择的分类cat_id值');
            $table->string('name')->default('')->comment('导航栏名称');
            $table->boolean('ifshow')->default(0)->index('ifshow')->comment('是否显示');
            $table->boolean('vieworder')->default(0)->comment('排序');
            $table->boolean('opennew')->default(0)->comment('是否新窗口打开');
            $table->string('url')->default('')->comment('跳转url');
            $table->string('type', 10)->default('')->index('type')->comment('类型');
            $table->string('pic')->default('')->comment('图标');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'H5导航栏'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('touch_nav');
    }
}
