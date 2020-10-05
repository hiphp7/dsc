<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSmsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'sms';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 60)->comment('短信名称');
            $table->string('code', 60)->comment('短信code');
            $table->string('description')->default('')->comment('短信说明');
            $table->text('sms_configure')->comment('短信配置,序列化');
            $table->tinyInteger('enable')->unsigned()->default(0)->comment('启用状态：0 关闭 1 开启');
            $table->tinyInteger('default')->unsigned()->default(0)->comment('是否默认，0 否 1 是');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
            $table->integer('sort')->unsigned()->default(50)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '短信插件表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms');
    }
}
