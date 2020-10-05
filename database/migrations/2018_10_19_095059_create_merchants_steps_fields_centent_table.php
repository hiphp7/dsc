<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsStepsFieldsCententTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_steps_fields_centent';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('tid')->unsigned()->index('tid')->comment('关联merchants_steps_title表tid');
            $table->text('textFields')->nullable()->comment('字段');
            $table->text('fieldsDateType')->nullable()->comment('字段数据类型');
            $table->text('fieldsLength')->nullable()->comment('字段长度');
            $table->text('fieldsNotnull')->nullable()->comment('字段是否为空');
            $table->text('fieldsFormName')->nullable()->comment('字段注释名称');
            $table->text('fieldsCoding')->nullable()->comment('字段编码：如UTF8');
            $table->text('fieldsForm')->nullable()->comment('字段标签类型');
            $table->text('fields_sort')->nullable()->comment('排序');
            $table->text('will_choose')->nullable()->comment('是否必选项：0 否，1 是');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '入驻流程字段信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_steps_fields_centent');
    }
}
