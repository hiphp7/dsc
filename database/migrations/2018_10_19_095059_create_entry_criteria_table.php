<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEntryCriteriaTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'entry_criteria';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父类ID号');
            $table->string('criteria_name')->comment('入驻标准名称');
            $table->decimal('charge', 10)->unsigned()->default(0.00)->comment('入驻费用');
            $table->string('standard_name', 60)->comment('项目名称');
            $table->string('type', 10)->comment('商家等级入驻类型');
            $table->boolean('is_mandatory')->default(0)->comment('是否必填');
            $table->string('option_value')->comment('相关值');
            $table->boolean('data_type')->default(0)->comment('文本类型');
            $table->boolean('is_cumulative')->default(1)->comment('是否累加');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家等级入驻标准'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('entry_criteria');
    }
}
