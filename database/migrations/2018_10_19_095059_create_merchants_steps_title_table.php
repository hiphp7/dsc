<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsStepsTitleTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_steps_title';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('tid')->comment('自增ID');
            $table->boolean('fields_steps')->default(0)->comment('同dsc_merchants_steps_process的id');
            $table->string('fields_titles')->default('')->comment('内容标题');
            $table->boolean('steps_style')->default(0)->comment('所属流程');
            $table->string('titles_annotation')->default('')->comment('标题注释');
            $table->text('fields_special')->nullable()->comment('特殊说明');
            $table->string('special_type')->default('')->comment('显示样式');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '入驻流程步骤基本流程'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_steps_title');
    }
}
