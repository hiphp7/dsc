<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateHomeTemplatesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'home_templates';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('temp_id')->comment('自增ID');
            $table->integer('rs_id')->unsigned()->default(0)->index('rs_id')->comment('卖场ID');
            $table->string('code', 60)->default('')->comment('模板名称');
            $table->boolean('is_enable')->default(0)->index('is_enable')->comment('是否使用');
            $table->string('theme', 160)->default('')->comment('商城模板名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '首页可视化模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('home_templates');
    }
}
