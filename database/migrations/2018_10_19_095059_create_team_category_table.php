<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTeamCategoryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'team_category';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name')->default('')->comment('分类名称');
            $table->integer('parent_id')->unsigned()->default(0)->comment('父级id');
            $table->string('content')->default('')->comment('分类描述');
            $table->string('tc_img')->default('')->comment('分类图标');
            $table->integer('sort_order')->unsigned()->default(0)->comment('排序');
            $table->boolean('status')->default(1)->comment('显示0否 1显示');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '拼团分类'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('team_category');
    }
}
