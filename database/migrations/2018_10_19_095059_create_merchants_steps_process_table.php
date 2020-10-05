<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsStepsProcessTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_steps_process';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->boolean('process_steps')->default(0)->comment('所属流程');
            $table->string('process_title')->default('')->comment('流程信息标题');
            $table->integer('process_article')->unsigned()->default(0)->comment('文章ID');
            $table->integer('steps_sort')->unsigned()->default(0)->comment('排序');
            $table->boolean('is_show')->default(1)->comment('是否显示');
            $table->string('fields_next')->default('')->comment('下一步标题');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '入驻流程步骤基本信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_steps_process');
    }
}
